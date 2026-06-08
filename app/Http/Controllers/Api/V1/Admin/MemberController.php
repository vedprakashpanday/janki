<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Branch;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    // ==========================================
    // 🔥 HELPER: CHECK PERMISSION INTERNALLY
    // ==========================================
    private function checkPermission($action)
    {
        $user = auth()->user();
        if (!$user) return false;

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array($user->email, $developerEmails) || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin'))) {
            return true;
        }

        // Fetching ACTIVE (Live) permissions from our Base Controller
        $livePerms = self::getLiveActivePermissions($user);

        // Handle variations of add permission
        if ($action === 'add') {
            return in_array("member_add_direct", $livePerms) ||
                in_array("member_add_request", $livePerms);
        }

        return in_array("member_{$action}", $livePerms);
    }


    // ==========================================
    // 1. GET: Server-Side Data Load
    // ==========================================
   public function index(Request $request)
    {
        if (!$this->checkPermission('view') && !$this->checkPermission('add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access! Your view permission may have expired.'], 403);
        }

        $query = Member::with(['branch.company']);

        // 🛡️ DATA FILTER LOGIC (Strict RBAC Lock)
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $query->where('branch_id', $user->branch_id);
        }

        // 🔥 MULTI-SELECT FILTERS (For Task Assignment) 🔥
        if ($request->filled('company_ids')) {
            $query->whereIn('company_id', explode(',', $request->company_ids));
        }

        // Branches WITH Head Office (HO) Magic
        if ($request->filled('branch_ids')) {
            $branchIds = explode(',', $request->branch_ids);
            $hoCompanyIds = [];
            $normalBranchIds = [];

            foreach ($branchIds as $bId) {
                if (str_starts_with($bId, 'HO_')) {
                    $hoCompanyIds[] = str_replace('HO_', '', $bId); // Extract Company ID
                } else {
                    $normalBranchIds[] = $bId; // Normal branch id
                }
            }

            $query->where(function($q) use ($normalBranchIds, $hoCompanyIds) {
                if (count($normalBranchIds) > 0) {
                    $q->whereIn('branch_id', $normalBranchIds);
                }
                if (count($hoCompanyIds) > 0) {
                    $q->orWhere(function($subQ) use ($hoCompanyIds) {
                        $subQ->whereIn('company_id', $hoCompanyIds)->whereNull('branch_id');
                    });
                }
            });
        }

        // Departments aur Designations
        if ($request->filled('department_ids')) {
            $query->whereIn('department_id', explode(',', $request->department_ids));
        }
        if ($request->filled('designation_ids')) {
            $query->whereIn('designation_id', explode(',', $request->designation_ids));
        }

        // 🔍 GLOBAL SEARCH (Fixed Security/Scope Leak)
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('member_name', 'LIKE', "%{$search}%")
                  ->orWhere('member_id', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%")
                  ->orWhere('sponsor_id', 'LIKE', "%{$search}%");
            });
        }

        $totalData = Member::count();
        $totalFiltered = $query->count();

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        // Fetch ALL if length is -1 (used in our Offcanvas assignment)
        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $members = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $members
        ]);
    }


    // ==========================================
    // 2. STORE
    // ==========================================
    public function store(Request $request)
    {
        if (!$this->checkPermission('add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! You do not have permission to add members.'], 403);
        }

        $request->validate([
            'company_id' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required',
            'designation' => 'required',
            'member_name' => 'required',
            'mobile' => 'required',
            'doj' => 'required|date'
        ]);

        $data = $request->except(['_token']);
        $branch = \App\Models\Branch::with('company')->findOrFail($request->branch_id);

        // 🛡️ STORE/UPDATE OWNERSHIP & STRICT SPONSOR CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isAdmin = $user->hasRole(['CEO', 'Director', 'Branch Manager', 'super_admin', 'Developer']) || in_array(strtolower($user->email), $developerEmails);

        if (!$isAdmin) {
            if (isset($branch) && $branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            $data['sponsor_id'] = $user->member_id;
            $data['sponsor_name'] = $user->name;
        } else {
            if ($request->sponsor_name === 'SYSTEM ROOT') {
                $data['sponsor_name'] = 'SYSTEM ROOT';
            } elseif (!empty($request->sponsor_id)) {
                $sponsor = \App\Models\Member::where('member_id', $request->sponsor_id)->first();
                $data['sponsor_name'] = $sponsor ? $sponsor->member_name : null;
            }
        }

        $companyPrefix = $branch->company ? $branch->company->company_code : 'CMP';

        $branchParts = explode('/', $branch->branch_id);
        $stateCode = $branchParts[1] ?? 'ST';
        $rawBranchCode = $branchParts[2] ?? 'DIST';

        $distCode = preg_replace('/[^a-zA-Z]/', '', $rawBranchCode);
        $branchNum = preg_replace('/[^0-9]/', '', $rawBranchCode);
        if (empty($branchNum)) {
            $branchNum = '1';
        }
        $formattedBranchNum = str_pad($branchNum, 2, '0', STR_PAD_LEFT);

        $designationObj = \App\Models\Designation::where('designation_name', $request->designation)->first();
        $data['designation_id'] = $designationObj ? $designationObj->id : null;
        $desigCode = $designationObj ? strtoupper($designationObj->designation_code) : 'MB';

        $year = date('Y', strtotime($request->doj));

        $lastMember = Member::where('branch_id', $branch->id)->orderBy('id', 'desc')->first();
        if ($lastMember && $lastMember->member_id) {
            $lastIdParts = explode('/', $lastMember->member_id);
            $lastSeqStr = $lastIdParts[count($lastIdParts) - 2] ?? '0';
            $nextSeq = ((int) $lastSeqStr) + 1;
        } else {
            $nextSeq = 1;
        }

        $sequence = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

        $data['member_id'] = "{$companyPrefix}/{$stateCode}/{$distCode}/{$formattedBranchNum}/{$desigCode}/{$sequence}/{$year}";

        $firstName = explode(' ', $request->member_name)[0];
        $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
        $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_number ?? '0000'), -4);
        $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

        $fileFields = [
            'aadharcard',
            'pancard',
            'bankpassbook',
            'drivinglicense',
            'passport',
            'passport_photo',
            'sign',
            'tenthmarksheet',
            'twelvethmarksheet',
            'graduationcertificate',
            'pgcertificate',
            'otherdoc',
            'nom_aadharcard',
            'nom_pancard',
            'nom_bankpassbook',
            'nom_drivinglicense',
            'nom_passport',
            'nom_passport_photo',
            'nom_tenthmarksheet',
            'nom_twelvethmarksheet',
            'nom_graduationcertificate',
            'nom_pgcertificate',
            'nom_otherdoc'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/members'), $filename);
                $data[$field] = 'uploads/members/' . $filename;
            }
        }

        $member = Member::create($data);

        return response()->json([
            'status' => 'success',
            'message' => "Member saved! ID: {$member->member_id}"
        ]);
    }

    // ==========================================
    // 3. SHOW
    // ==========================================
    public function show($id)
    {
        if (!$this->checkPermission('view') && !$this->checkPermission('edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access! Your view permission may have expired.'], 403);
        }

        $member = Member::with(['branch.company'])->findOrFail($id);

        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($member->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $member
        ]);
    }

    // ==========================================
    // 4. UPDATE
    // ==========================================
    public function update(Request $request, $id)
    {
        if (!$this->checkPermission('edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! Your edit permission may have expired.'], 403);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'designation' => 'required',
            'member_name' => 'required',
            'mobile' => 'required',
            'doj' => 'required|date'
        ]);

        $member = Member::findOrFail($id);

        // 🛡️ STORE/UPDATE OWNERSHIP & STRICT SPONSOR CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isAdmin = $user->hasRole(['CEO', 'Director', 'Branch Manager', 'super_admin', 'Developer']) || in_array(strtolower($user->email), $developerEmails);

        if (!$isAdmin) {
            if (isset($branch) && $branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            $data['sponsor_id'] = $user->member_id;
            $data['sponsor_name'] = $user->name;
        } else {
            if ($request->sponsor_name === 'SYSTEM ROOT') {
                $data['sponsor_name'] = 'SYSTEM ROOT';
            } elseif (!empty($request->sponsor_id)) {
                $sponsor = \App\Models\Member::where('member_id', $request->sponsor_id)->first();
                $data['sponsor_name'] = $sponsor ? $sponsor->member_name : null;
            }
        }

        $data = $request->except(['_token', 'member_id', '_method']);

        if (!$isAdmin) {
            unset($data['sponsor_id']);
            unset($data['sponsor_name']);
        }

        $designationObj = \App\Models\Designation::where('designation_name', $request->designation)->first();
        if ($designationObj) {
            $data['designation_id'] = $designationObj->id;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $fileFields = [
            'aadharcard',
            'pancard',
            'bankpassbook',
            'drivinglicense',
            'passport',
            'passport_photo',
            'sign',
            'tenthmarksheet',
            'twelvethmarksheet',
            'graduationcertificate',
            'pgcertificate',
            'otherdoc',
            'nom_aadharcard',
            'nom_pancard',
            'nom_bankpassbook',
            'nom_drivinglicense',
            'nom_passport',
            'nom_passport_photo',
            'nom_tenthmarksheet',
            'nom_twelvethmarksheet',
            'nom_graduationcertificate',
            'nom_pgcertificate',
            'nom_otherdoc'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/members'), $filename);
                $data[$field] = 'uploads/members/' . $filename;
            }
        }

        $member->update($data);
        return response()->json(['status' => 'success', 'message' => 'Member updated successfully.']);
    }

    // ==========================================
    // 5. DESTROY
    // ==========================================
    public function destroy($id)
    {
        if (!$this->checkPermission('delete')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! Your delete permission may have expired.'], 403);
        }

        $member = Member::findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($member->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        $member->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }
}
