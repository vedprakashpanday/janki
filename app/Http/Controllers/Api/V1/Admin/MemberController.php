<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Branch;
use App\Models\MemberServiceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\MediaConverterService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    private function checkPermission($action)
    {
        $user = auth()->user();
        if (!$user) return false;

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array($user->email, $developerEmails) || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin'))) {
            return true;
        }

        $livePerms = self::getLiveActivePermissions($user);

        if ($action === 'add') {
            return in_array("member_add_direct", $livePerms) || in_array("member_add_request", $livePerms);
        }
        return in_array("member_{$action}", $livePerms);
    }

    // 🔥 HELPER: Master HO Employee ko full scope dene ke liye
    private function hasFullScope($user)
    {
        if (!$user) return false;

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if ($user->hasRole(['CEO', 'Director', 'Branch Manager', 'super_admin', 'Developer']) || in_array(strtolower($user->email ?? ''), $developerEmails)) {
            return true;
        }

        // Agar branch_id null hai aur parent_id null hai, toh Master HO
        if (empty($user->branch_id) && !empty($user->company_id)) {
            $comp = \App\Models\Company::find($user->company_id);
            if ($comp && empty($comp->parent_id)) {
                return true;
            }
        }
        return false;
    }

    public function getTransferredMembers(Request $request)
    {
        $companyId = $request->company_id;

        // 1. Fetch from Members Table
        $memberQuery = Member::where(function ($q) {
            $q->where('mem_status', 'Transferred')
                ->orWhere('mem_status', 'transferred');
        });

        if ($companyId) {
            $memberQuery->where('company_id', $companyId);
        }

        $transferredMembers = $memberQuery->get()->map(function ($m) {
            $m->source_type = 'member';
            return $m;
        });

        // 2. Fetch from Employee (adm_regist) Table
        $employeeQuery = \App\Models\Employee::where(function ($q) {
            $q->where('employment_stage', 'Transferred')
                ->orWhere('employment_stage', 'transferred');
        });

        if ($companyId) {
            $employeeQuery->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhere('transferred_to_company', $companyId);
            });
        }

        $transferredEmployees = $employeeQuery->get()->map(function ($e) {
            $e->source_type = 'employee';
            $e->member_name = $e->name ?? $e->member_name ?? $e->full_name;
            $e->member_id = $e->emp_id ?? $e->member_id ?? 'EMP-' . $e->id;
            return $e;
        });

        $allTransferred = $transferredMembers->concat($transferredEmployees);

        return response()->json(['status' => 'success', 'data' => $allTransferred]);
    }

public function index(Request $request)
    {
        $hasView = $this->checkPermission('view');
        
        // 1. Global Context लायें ताकि पता चले लॉगिन किसने किया है
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // 2. Check करें कि क्या लॉगिन करने वाला Super Admin, CEO या Director है
        $isSuperUser = $context ? ($context->is_god || in_array($context->role_level, ['ceo', 'director', 'admin'])) : false;

        // 3. अगर View Permission नहीं है और वो Super User भी नहीं है, तो खाली डेटा भेजें 
        // (हालांकि Frontend से रिक्वेस्ट आएगी ही नहीं, लेकिन सिक्योरिटी के लिए ये जरूरी है)
        if (!$hasView && !$isSuperUser) {
            return response()->json([
                "draw" => intval($request->input('draw')),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ]);
        }

        $query = Member::with(['branch.company']);

        if (!$this->hasFullScope($user)) {
            $query->where('branch_id', $user->branch_id);
        }

        // 4. 🔥 MAIN FILTER LOGIC (Employee और Member के लिए सिर्फ आज का डेटा) 🔥
        if (!$isSuperUser) {
            // Dono (Employee/Member) ko sirf aaj ka data dikhana hai
            $query->whereDate('members.created_at', Carbon::today());

            if ($context && $context->is_member) {
                // Member: सिर्फ वही डेटा जिसका Sponsor ये खुद हो
                $memberId = $user->member_id ?? $context->profile_id;
                $query->where('sponsor_id', $memberId);
            } 
            elseif ($context && $context->is_employee) {
                // Employee: सिर्फ खुद का क्रिएट किया हुआ डेटा
                $query->where('created_by', $user->id);
            }
        }

        // --- (बाकी पुराने फिल्टर्स वैसे ही रहेंगे) ---
        if ($request->filled('company_ids')) {
            $query->whereIn('company_id', explode(',', $request->company_ids));
        }

        if ($request->filled('branch_ids')) {
            $branchIds = explode(',', $request->branch_ids);
            $hoCompanyIds = [];
            $normalBranchIds = [];

            foreach ($branchIds as $bId) {
                if (str_starts_with($bId, 'HO_')) {
                    $hoCompanyIds[] = str_replace('HO_', '', $bId);
                } else {
                    $normalBranchIds[] = $bId;
                }
            }

            $query->where(function ($q) use ($normalBranchIds, $hoCompanyIds) {
                if (count($normalBranchIds) > 0) $q->whereIn('branch_id', $normalBranchIds);
                if (count($hoCompanyIds) > 0) {
                    $q->orWhere(function ($subQ) use ($hoCompanyIds) {
                        $subQ->whereIn('company_id', $hoCompanyIds)->whereNull('branch_id');
                    });
                }
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('member_name', 'LIKE', "%{$search}%")
                    ->orWhere('member_id', 'LIKE', "%{$search}%")
                    ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        $totalData = Member::count();
        $totalFiltered = $query->count();
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) $query->offset($start)->limit($length);
        // MySQL ki string function se '/' ke baad wala hissa nikal kar number me convert kar rahe hain
$members = $query->orderByRaw("CAST(SUBSTRING_INDEX(member_id, '/', -1) AS UNSIGNED) DESC")->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $members
        ]);
    }
  public function store(Request $request)
    {
        if (!$this->checkPermission('add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $branchIdRaw = (string) $request->branch_id;
        $isHO = empty($branchIdRaw) || $branchIdRaw === 'null' || str_starts_with($branchIdRaw, 'HO_');

        $rules = [
            'company_id' => 'required',
            'department_id' => 'required',
            'designation' => 'required',
            'member_name' => 'required',
            'mobile' => 'required',
            'doj' => 'required|date'
        ];

        if (!$isHO) $rules['branch_id'] = 'required|exists:branches,id';
        $request->validate($rules);

        $data = $request->except(['_token', 'manual_series', 'member_id', 'banks']); // member_id bypass
        $user = auth()->user();

        // 🔥 NAYA: created_by को लॉगिन करने वाले की ID से सेव करें
        $data['created_by'] = $user->id;

        // 🔥 FIX: SMART MANUAL SERIES DETECTION 🔥
        // Agar user ne 'Member ID' me khud se 001 type kiya hai, toh usey pakad lo
        $manualSeries = $request->manual_series;
        if (empty($manualSeries) && !empty($request->member_id)) {
            $parts = explode('/', $request->member_id);
            $lastPart = end($parts);
            if (is_numeric($lastPart) && (int)$lastPart >= 1 && (int)$lastPart <= 7) {
                $manualSeries = (int)$lastPart;
            }
        }

        $livePerms = self::getLiveActivePermissions($user);
        $hasDirectAdd = in_array("member_add_direct", $livePerms) || $this->hasFullScope($user);
        $data['status'] = $hasDirectAdd ? 'active' : 'pending';

        $branch = null;
        $companyCode = 'CMP';

        if (!$isHO) {
            $branch = Branch::with('company')->findOrFail($request->branch_id);
            $data['branch_id'] = $branch->id;
            $data['company_id'] = $branch->company_id;
            $companyCode = $branch->company ? $branch->company->company_code : 'CMP';
        } else {
            $data['branch_id'] = null;
            $company = \App\Models\Company::find($request->company_id);
            $companyCode = $company ? $company->company_code : 'CMP';
        }

        // Dynamic Default Sponsor (CompanyCode-M/001)
        $defaultSponsorId = "{$companyCode}-M/001";

        if (!$this->hasFullScope($user)) {
            if (!$isHO && $branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            $data['sponsor_id'] = $user->member_id;
            $data['sponsor_name'] = $user->name;
        } else {
            if (empty($request->sponsor_id) || strtoupper($request->sponsor_name) === 'SYSTEM ROOT') {
                // 🔥 FIX: 001, 002 aur 003 teeno ko System Root banaya gaya hai
                if ($manualSeries >= 1 && $manualSeries <= 3) { 
                    $seqStr = str_pad($manualSeries, 3, '0', STR_PAD_LEFT);
                    $data['sponsor_id'] = "{$companyCode}-M/{$seqStr}";
                    $data['sponsor_name'] = 'System Root';
                } else {
                    // Agar 004+ entry ho rahi hai aur sponsor nahi hai, to by default 001 ko sponsor manega
                    $defaultSponsorId = "{$companyCode}-M/001";
                    $sponsor = Member::where('member_id', $defaultSponsorId)->first();
                    $data['sponsor_id'] = $defaultSponsorId;
                    $data['sponsor_name'] = $sponsor ? $sponsor->member_name : 'System Root';
                }
            } else {
                $sponsor = Member::where('member_id', $request->sponsor_id)->first();
                if ($sponsor) {
                    $data['sponsor_id'] = $sponsor->member_id;
                    $data['sponsor_name'] = $sponsor->member_name;
                } else {
                    $data['sponsor_id'] = $request->sponsor_id;
                    $data['sponsor_name'] = 'Unknown';
                }
            }
        }

        $designationObj = \App\Models\Designation::where('designation_name', $request->designation)->first();
        $data['designation_id'] = $designationObj ? $designationObj->id : null;
        $desigCode = $designationObj ? strtoupper($designationObj->designation_code) : 'MB';

        $data['mem_status'] = $request->mem_status ?? 'On Board';
        $data['grade'] = $request->grade ?? null;

        $firstName = explode(' ', $request->member_name)[0];
        $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
        $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_number ?? '0000'), -4);
        $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

        $fileFields = ['aadharcard', 'pancard', 'bankpassbook', 'drivinglicense', 'passport', 'passport_photo', 'sign', 'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc', 'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport', 'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate', 'nom_pgcertificate', 'nom_otherdoc'];
        $converter = new MediaConverterService();
        $convertibleExtensions = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'mp4', 'mov', 'avi', 'mkv', 'webm'];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $extension = strtolower($file->getClientOriginalExtension());

                if (in_array($extension, $convertibleExtensions)) {
                    $media = $converter->uploadAndConvert($file);
                    if ($media) $data[$field] = $media->file_path;
                } else {
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $file->move(public_path('uploads/members'), $filename);
                    $data[$field] = 'uploads/members/' . $filename;
                }
            }
        }

       DB::beginTransaction();
        try {
            // Frontend se aayi auto-filled ID direct assign karenge
            $data['member_id'] = $request->member_id; 
            
            $member = Member::create($data);

            if ($request->filled('grade')) {
                $member->syncRoles([$request->grade]);
            }

            $finalMemberId = $member->member_id;

            // Service Record ka sequence nikalo (Ab Zone A/B ki zarurat nahi)
            $maxSvcSeq = 0; 
            $allSvcRecords = MemberServiceRecord::where('company_code', $companyCode)->get();
            foreach ($allSvcRecords as $record) {
                $parts = explode('/', $record->service_code);
                $lastPart = end($parts);
                $seqStr = explode('-', $lastPart)[0]; 
                if (is_numeric($seqStr)) {
                    $seq = (int)$seqStr;
                    if ($seq > $maxSvcSeq) {
                        $maxSvcSeq = $seq;
                    }
                }
            }
            $nextSvcSeq = str_pad($maxSvcSeq + 1, 3, '0', STR_PAD_LEFT);
            $svcCode = "{$companyCode}-M/SVC/{$nextSvcSeq}";

            // Update member's service_id
            $member->service_id = $svcCode;
            $member->save();

            MemberServiceRecord::create([
                'service_code' => $svcCode,
                'member_id_ref' => $member->id,
                'company_code' => $companyCode,
                'action_type' => 'Onboarding',
                'action_date' => $member->doj,
                'action_details' => ['designation' => $member->designation, 'branch_id' => $member->branch_id],
                'remarks' => 'Initial Onboarding / Associate Added'
            ]);

            // 🔥 NAYA: Bank Details Save (Multiple JSON to Rows)
            $banks = $request->banks ?? [];
            foreach ($banks as $b) {
                if (!empty($b['account_no'])) { // Check if account no exists
                    \Illuminate\Support\Facades\DB::table('tbl_bank_details')->insert([
                        'member_id' => $member->member_id,
                        'account_name'  => $b['account_name'] ?? null,
                        'account_no'    => $b['account_no'] ?? null,
                        'account_type'  => $b['account_type'] ?? null,
                        'bank_name'     => $b['bank_name'] ?? null,
                        'branch'        => $b['branch'] ?? null,
                        'ifsc_code'     => $b['ifsc_code'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => "Member saved! ID: {$finalMemberId}"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function assignAndRearrangeId($newMember, $branch, $desigCode, $manualSeries = null)
    {
        $companyId = $newMember->company_id;
        $company = \App\Models\Company::find($companyId);
        $companyPrefix = $company ? $company->company_code : 'CMP';

        $isZoneA = ($manualSeries !== null && (int)$manualSeries >= 1 && (int)$manualSeries <= 7);
        
        // 🔥 UPDATE 1: Zone A (001-007) ke liye No Shuffling. Direct Assignment.
        if ($isZoneA) {
            $seqStr = str_pad($manualSeries, 3, '0', STR_PAD_LEFT);
            $generatedId = "{$companyPrefix}-M/{$seqStr}";
            
            // Check agar ye manual ID pehle se kisi aur ke paas toh nahi
            $exists = Member::where('company_id', $companyId)->where('member_id', $generatedId)->where('id', '!=', $newMember->id)->exists();
            if ($exists) {
                throw new \Exception("Ye Manual Series ({$generatedId}) is company mein pehle se kisi aur ko assigned hai.");
            }
            
            $newMember->member_id = $generatedId;
            $newMember->save();
            return $generatedId;
        }

        // Zone B Logic (008+): Yahan Shuffling hogi
        $allCompanyMembers = Member::where('company_id', $companyId)->where('id', '!=', $newMember->id)->get();

        $zoneBMembers = collect([]);
        foreach ($allCompanyMembers as $m) {
            if (str_starts_with($m->member_id, 'TEMP')) continue;
            $parts = explode('/', $m->member_id);
            $seqPart = end($parts);
            if (is_numeric($seqPart)) {
                $seq = (int)$seqPart;
                if ($seq >= 8) {
                    $zoneBMembers->push($m);
                }
            }
        }

        $zoneBMembers->push($newMember);
        $sortedMembers = $zoneBMembers->sortBy(function ($m) {
            return Carbon::parse($m->doj)->timestamp . '_' . $m->id;
        })->values();

        $startSeq = 8;

        // Two-Pass Trick
        foreach ($sortedMembers as $m) {
            $m->member_id = 'REARRANGE-' . uniqid() . '-' . $m->id;
            $m->save();
        }

        $finalIdForNewMember = '';
        foreach ($sortedMembers as $index => $m) {
            $newSeq = $startSeq + $index;
            $seqStr = str_pad($newSeq, 3, '0', STR_PAD_LEFT);
            $generatedId = "{$companyPrefix}-M/{$seqStr}";
            
            $m->member_id = $generatedId;
            $m->save();
            
            if ($m->id == $newMember->id) {
                $finalIdForNewMember = $generatedId;
            }
        }
        
        return $finalIdForNewMember;
    }
    public function show($id)
    {
        if (!$this->checkPermission('view') && !$this->checkPermission('edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access!'], 403);
        }

        $member = Member::with(['company','branch.company', 'department'])->findOrFail($id);
        $user = auth()->user();

        if (!$this->hasFullScope($user)) {
            if ($member->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }
        
        // 🔥 NAYA: Bank details manually fetch kar rahe hain
        $member->banks = \Illuminate\Support\Facades\DB::table('tbl_bank_details')
                            ->where('member_id', $member->member_id)
                            ->get();

        return response()->json(['status' => 'success', 'data' => $member]);
    }

  public function update(Request $request, $id)
    {
        if (!$this->checkPermission('edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $member = Member::findOrFail($id);
        $user = auth()->user();
        $branchIdRaw = (string) $request->branch_id;
        $isHO = empty($branchIdRaw) || $branchIdRaw === 'null' || str_starts_with($branchIdRaw, 'HO_');
        $branch = null;

        $data = $request->except(['_token', 'member_id', '_method', 'manual_series', 'banks']);

        if (!$isHO) {
            $request->validate(['branch_id' => 'required|exists:branches,id']);
            $branch = Branch::with('company')->findOrFail($request->branch_id);
            $data['branch_id'] = $branch->id;
            $data['company_id'] = $branch->company_id;
        } else {
            $data['branch_id'] = null;
        }

        if (!$this->hasFullScope($user)) {
            unset($data['sponsor_id']);
            unset($data['sponsor_name']);
        } else {
            if (empty($request->sponsor_id) || $request->sponsor_name === 'SYSTEM ROOT') {
                $data['sponsor_id'] = 'ABDPL-M/001';
                $data['sponsor_name'] = 'Amitabh Kumar';
            } else {
                $sponsor = Member::where('member_id', $request->sponsor_id)->first();
                if ($sponsor) {
                    $data['sponsor_id'] = $sponsor->member_id;
                    $data['sponsor_name'] = $sponsor->member_name;
                } else {
                    $data['sponsor_id'] = 'ABDPL-M/001';
                    $data['sponsor_name'] = 'Amitabh Kumar';
                }
            }
        }

        $designationObj = \App\Models\Designation::where('designation_name', $request->designation)->first();
        if ($designationObj) $data['designation_id'] = $designationObj->id;
        
        // 🔥 PLAIN TEXT SAVING (Hash hata diya)
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $fileFields = ['aadharcard', 'pancard', 'bankpassbook', 'drivinglicense', 'passport', 'passport_photo', 'sign', 'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc', 'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport', 'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate', 'nom_pgcertificate', 'nom_otherdoc'];
        $converter = new MediaConverterService();
        $convertibleExtensions = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'mp4', 'mov', 'avi', 'mkv', 'webm'];

foreach ($fileFields as $field) {
    if ($request->hasFile($field)) {
        
        // 🔥 NAYA: Purani file ko server se delete karein agar wo exist karti hai
        if (!empty($member->$field) && \Illuminate\Support\Facades\File::exists(public_path($member->$field))) {
            \Illuminate\Support\Facades\File::delete(public_path($member->$field));
        }

        $file = $request->file($field);
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, $convertibleExtensions)) {
            $media = $converter->uploadAndConvert($file);
            if ($media) $data[$field] = $media->file_path;
        } else {
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $file->move(public_path('uploads/members'), $filename);
            $data[$field] = 'uploads/members/' . $filename;
        }
    }
}

        DB::beginTransaction();
        try {
            $oldDesignation = $member->designation;
            $oldStatus = $member->mem_status;

            $member->update($data);
              if ($request->filled('grade')) {
                $member->syncRoles([$request->grade]);
            }

            if ($oldDesignation != $request->designation || $oldStatus != $request->mem_status) {
                $companyCode = $branch && $branch->company ? $branch->company->company_code : (\App\Models\Company::find($member->company_id)->company_code ?? 'CMP');

                $maxSvcSeq = 0;
                $allSvcRecords = MemberServiceRecord::where('company_code', $companyCode)->get();
                foreach ($allSvcRecords as $record) {
                    $parts = explode('/', $record->service_code);
                    $lastPart = end($parts);
                    $seqStr = explode('-', $lastPart)[0];
                    if (is_numeric($seqStr)) {
                        $seq = (int)$seqStr;
                        if ($seq > $maxSvcSeq) {
                            $maxSvcSeq = $seq;
                        }
                    }
                }

                $nextSvcSeq = str_pad($maxSvcSeq + 1, 3, '0', STR_PAD_LEFT);
                $svcCode = "{$companyCode}-M/SVC/{$nextSvcSeq}-" . substr(uniqid(), -4);

                MemberServiceRecord::create([
                    'service_code' => $svcCode,
                    'member_id_ref' => $member->id,
                    'company_code' => $companyCode,
                    'action_type' => 'Update/Modification',
                    'action_date' => date('Y-m-d'),
                    'action_details' => [
                        'old_designation' => $oldDesignation,
                        'new_designation' => $request->designation,
                        'old_status' => $oldStatus,
                        'new_status' => $request->mem_status
                    ],
                    'remarks' => 'Profile Updated via Admin'
                ]);
            }

            $banks = $request->banks ?? [];
            \Illuminate\Support\Facades\DB::table('tbl_bank_details')->where('member_id', $member->member_id)->delete();
            
            foreach ($banks as $b) {
                if (!empty($b['account_no'])) {
                    \Illuminate\Support\Facades\DB::table('tbl_bank_details')->insert([
                        'member_id' => $member->member_id,
                        'account_name'  => $b['account_name'] ?? null,
                        'account_no'    => $b['account_no'] ?? null,
                        'account_type'  => $b['account_type'] ?? null,
                        'bank_name'     => $b['bank_name'] ?? null,
                        'branch'        => $b['branch'] ?? null,
                        'ifsc_code'     => $b['ifsc_code'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Member updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    public function destroy($id)
    {
        if (!$this->checkPermission('delete')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $member = Member::findOrFail($id);
        $user = auth()->user();

        if (!$this->hasFullScope($user)) {
            if ($member->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        $member->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }

   public function getNextId(Request $request)
    {
        $companyId = $request->company_id;
        if (!$companyId) return response()->json(['next_id' => '']);

        $company = \App\Models\Company::find($companyId);
        $compCode = $company ? $company->company_code : 'CMP';

        $members = Member::where('company_id', $companyId)->get();
        $maxSeq = 0; // Ab directly 0 se start karenge

        foreach ($members as $m) {
            $parts = explode('/', $m->member_id);
            $seqPart = end($parts);
            if (is_numeric($seqPart)) {
                $seq = (int)$seqPart;
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }
        
        $nextSeq = str_pad($maxSeq + 1, 3, '0', STR_PAD_LEFT);
        return response()->json(['next_id' => "{$compCode}-M/{$nextSeq}"]);
    }

    public function bulkDelete(Request $request)
    {
        if (!$this->checkPermission('delete')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $ids = $request->ids;
        if (empty($ids)) return response()->json(['status' => 'error', 'message' => 'No members selected!'], 400);

        $user = auth()->user();

        DB::beginTransaction();
        try {
            $query = Member::whereIn('id', $ids);

            if (!$this->hasFullScope($user)) {
                $query->where('branch_id', $user->branch_id);
            }

            $query->delete();
            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Selected members deleted successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to delete members: ' . $e->getMessage()], 500);
        }
    }

  public function getAvailableDesignations(Request $request)
    {
        $sponsorId = $request->sponsor_id;
        $departmentId = $request->department_id;
        $editingMemberId = $request->member_id; // Edit ke waqt frontend se ye ID aani chahiye

        if (!$departmentId) return response()->json(['status' => 'success', 'data' => []]);

        $query = \App\Models\Designation::where('department_id', $departmentId)
                                        ->where('status', 'active');

        if (\App\Models\Member::count() == 0) {
            return response()->json(['status' => 'success', 'data' => $query->get()]);
        }

        // 🔥 NAYA RULE: Agar edit hone wala member 001 se 004 hai, toh sab dikhao
        if ($editingMemberId) {
            $parts = explode('/', $editingMemberId);
            $seq = (int) end($parts);
            if ($seq >= 1 && $seq <= 4) {
                return response()->json(['status' => 'success', 'data' => $query->orderBy('position', 'asc')->get()]);
            }
        }

        // 🔥 NAYA RULE: Agar sponsor 001 se 004 hai, toh sab dikhao
        $isRootSponsor = false;
        if ($sponsorId) {
            $parts = explode('/', $sponsorId);
            $seq = (int) end($parts);
            if ($seq >= 1 && $seq <= 4) {
                $isRootSponsor = true;
            }
        }

        if (!$sponsorId || $sponsorId === 'SYSTEM ROOT' || str_starts_with($sponsorId, 'TEMP') || $isRootSponsor) {
            return response()->json(['status' => 'success', 'data' => $query->orderBy('position', 'asc')->get()]);
        }

        // Normal Hierarchy Logic
        $sponsor = \App\Models\Member::where('member_id', $sponsorId)->first();
        if (!$sponsor || !$sponsor->designation_id) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $sponsorDesignation = \App\Models\Designation::find($sponsor->designation_id);
        if (!$sponsorDesignation || !isset($sponsorDesignation->position)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $sponsorPosition = (int) $sponsorDesignation->position;

        if ($sponsorPosition === 1) {
            $query->where('position', 1);
        } else {
            $query->where('position', '<', $sponsorPosition); // Strict Downline logic
        }

        $filteredDesignations = $query->orderBy('position', 'asc')->get();

        return response()->json(['status' => 'success', 'data' => $filteredDesignations]);
    }
    // ====================================================================
    // 🔥 NAYA: APPROVE & REJECT LOGIC 🔥
    // ====================================================================
    public function approve($id)
    {
        if (!$this->checkPermission('appr')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }
        $member = Member::findOrFail($id);
        $member->update(['status' => 'active']);
        return response()->json(['status' => 'success', 'message' => 'Member Approved & Activated!']);
    }

    public function reject($id)
    {
        if (!$this->checkPermission('rej')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }
        $member = Member::findOrFail($id);
        $member->update(['status' => 'inactive']); // Rejected entry inactive ho jayegi
        return response()->json(['status' => 'success', 'message' => 'Member Rejected!']);
    }


   public function exportExcel(Request $request)
    {
        $query = Member::with(['branch', 'company', 'department', 'designation']);

        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('id', $ids);
        } else {
            if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
            if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
            if ($request->filled('status')) $query->where('status', $request->status);
        }

        $members = $query->get();
        $fileName = 'Members_Export_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function() use ($members) {
            
            if (ob_get_level() > 0) {
                ob_clean(); 
            }
            
            $file = fopen('php://output', 'w');
            
            // 🔥 NAYA: Yahan Header me 'Sponsor Name' add kiya gaya hai
            fputcsv($file, ['S.No', 'Member ID', 'Name', 'Mobile', 'Sponsor Name', 'Designation', 'Company', 'Branch', 'Status']);
            
            $count = 1;
            foreach ($members as $member) {
                
                $designation = is_object($member->designation) ? ($member->designation->designation_name ?? 'N/A') : ($member->designation ?? 'N/A');
                $company = is_object($member->company) ? ($member->company->company_name ?? 'N/A') : 'N/A';
                $branch = is_object($member->branch) ? ($member->branch->branch_name ?? 'HO') : 'HO';
                $status = ucfirst((string)($member->status ?? ''));

                fputcsv($file, [
                    $count++,
                    $member->member_id ?? 'N/A',
                    $member->member_name ?? $member->member_name ?? 'N/A',
                    $member->mobile ?? 'N/A',
                    
                    // 🔥 NAYA: Yahan data me sponsor_name pass kar diya hai
                    $member->sponsor_name ?? 'N/A',
                    
                    $designation,
                    $company,
                    $branch,
                    $status
                ]);
            }
            fclose($file);
            
        }, $fileName, [
            "Content-type" => "text/csv",
        ]);
    }
    // ==========================================
    // PRINT FUNCTION WITH COMPONENT & WATERMARK
    // ==========================================
    public function printMembers(Request $request)
    {
        $query = Member::with(['branch', 'company', 'department', 'designation']);

        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('id', $ids);
        } else {
            if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
            if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        }

        $members = $query->get();

        // Header aur Watermark ke liye Company aur Branch nikalna
        // Default admin company le lenge agar specific filter nahi hai
        $companyId = $request->company_id ?? ($members->first()->company_id ?? 1);
        $branchId = $request->branch_id ?? ($members->first()->branch_id ?? null);

        $company = \App\Models\Company::find($companyId);
        $branch = \App\Models\Branch::find($branchId);

        return view('admin.members.print', compact('members', 'company', 'branch'));
    }

public function updateProfile(Request $request)
    {
        $user = auth()->user(); 
        
        // 1. Password Change Logic (Pure Plain Text)
        if ($request->has('current_password') && $request->has('new_password')) {
            
            if ($user->password !== $request->current_password) {
                return response()->json(['status' => 'error', 'message' => 'Current password incorrect']);
            }
            
            $user->password = $request->new_password;
        }

        // 2. Profile Image Upload
        if ($request->hasFile('profile_image')) {
            if ($user->passport_photo && File::exists(public_path($user->passport_photo))) {
                File::delete(public_path($user->passport_photo));
            }

            $converter = new MediaConverterService();
            $media = $converter->uploadAndConvert($request->file('profile_image'));
            
            if ($media) {
                $user->passport_photo = $media->file_path;
            }
        }

        // 3. Other Details Update
        $user->update($request->only(['mobile', 'address', 'alternate_mobile', 'email']));

        return response()->json(['status' => 'success', 'message' => 'Profile updated successfully']);
    }
// MemberController.php के अंदर
    public function searchDynamic(Request $request)
    {
        $query = \App\Models\Member::with('company');

        // अगर एम्प्लोयी किसी ख़ास कंपनी का है, तो उसे सिर्फ उसी कंपनी के मेंबर्स दिखें (ऑप्शनल सिक्योरिटी)
        if ($request->has('company_id') && !empty($request->company_id)) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('q') && strlen($request->q) >= 3) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('member_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('member_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('company', function($compQuery) use ($searchTerm) {
                      $compQuery->where('company_name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        } else {
            // अगर 3 लेटर से कम है, तो खाली रिस्पांस भेजें
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $members = $query->limit(20)->get();
        return response()->json(['status' => 'success', 'data' => $members]);
    }

  public function searchSponsorDynamic(Request $request) {
        $q = trim($request->q); // 🔥 Naya: Trim whitespaces
        if (strlen($q) < 3) return response()->json(['status' => 'success', 'data' => []]);

        $query = \App\Models\Member::where('status', 'active');
        $query->where(function($sq) use ($q) {
            $sq->where('member_name', 'LIKE', "%{$q}%")
               ->orWhere('member_id', 'LIKE', "%{$q}%");
        });

        if (!$this->hasFullScope(auth()->user())) {
             $query->where('branch_id', auth()->user()->branch_id);
        }

        $members = $query->limit(20)->get(['id', 'member_id', 'member_name', 'designation_id']);
        return response()->json(['status' => 'success', 'data' => $members]);
    }

 public function allTimeIndex(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();
        
        $query = Member::with(['branch.company']); 

        // Check Admin / God
        $isSuperUser = $context ? ($context->is_god || in_array($context->role_level, ['ceo', 'director', 'admin'])) : false;

        // STRICT FILTER LOGIC
        if (!$isSuperUser) {
            if ($context && $context->is_employee) {
                $query->where('created_by', $user->id);
            }
            elseif ($context && $context->is_member) {
                $memberId = $user->member_id ?? $context->profile_id;
                $query->where('sponsor_id', $memberId);
            }
        } elseif ($context->is_director) {
            $query->where('company_id', $user->company_id);
        }

        // 🔥 FIX 1: SEARCH LOGIC YAHAN ADD KIYA GAYA HAI 🔥
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('member_name', 'LIKE', "%{$search}%")
                    ->orWhere('member_id', 'LIKE', "%{$search}%")
                    ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        $totalData = Member::count();
        $totalFiltered = $query->count();
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) $query->offset($start)->limit($length);
       // MySQL ki string function se '/' ke baad wala hissa nikal kar number me convert kar rahe hain
$members = $query->orderByRaw("CAST(SUBSTRING_INDEX(member_id, '/', -1) AS UNSIGNED) DESC")->get();
        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $members
        ]);
    }

    // ====================================================
    // 🔥 UNIFIED DEPENDENCY SEARCH API'S (No Conflicts) 🔥
    // ====================================================
    public function searchCompanies(Request $request) {
        $q = $request->q;
        if (strlen($q) < 3) return response()->json(['status' => 'success', 'data' => []]);

        $context = $this->getGlobalContext();
        $query = \App\Models\Company::where('status', 'active')
            ->where(function($sq) use ($q) {
                $sq->where('company_name', 'LIKE', "%{$q}%")->orWhere('company_code', 'LIKE', "%{$q}%");
            });

        $isMasterHO = ($context->is_employee && empty($context->branch_id));
        if (!$context->is_god && !$context->is_director && !$isMasterHO && $context->company_id) {
            $query->where('id', $context->company_id);
        }

        return response()->json(['status' => 'success', 'data' => $query->limit(20)->get(['id', 'company_name', 'company_code'])]);
    }

   public function searchBranches(Request $request) {
       $q = trim($request->q);
        $companyId = $request->company_id;
        
        // Relax strict requirement for companyId if searching broadly, but usually it's needed
        if (strlen($q) < 3) return response()->json(['status' => 'success', 'data' => []]);

        $context = $this->getGlobalContext();
        $query = \App\Models\Branch::where('branch_status', 'active')
            ->where('branch_name', 'LIKE', "%{$q}%");

        // Apply company filter if provided
        if (!empty($companyId)) {
            $query->where('company_id', $companyId);
        }

        $isMasterHO = ($context->is_employee && empty($context->branch_id));
        // If not admin, not director, and not master HO, restrict to user's company
        if (!$context->is_god && !$context->is_director && !$isMasterHO && $context->company_id) {
            $query->where('company_id', $context->company_id);
        }

        return response()->json(['status' => 'success', 'data' => $query->limit(20)->get(['id', 'branch_name', 'branch_id'])]);
    }
    public function searchDepartments(Request $request) {
        $q = $request->q;
        $companyId = $request->company_id;
        if (strlen($q) < 3) return response()->json(['status' => 'success', 'data' => []]);

        $query = \App\Models\Department::where('status', 'active')
            ->where('department_name', 'LIKE', "%{$q}%")
            ->where('department_name', 'LIKE', "%associate%");

        return response()->json(['status' => 'success', 'data' => $query->limit(20)->get(['id', 'department_name'])]);
    }

    // ==========================================
    // DOWNLINE TREE API
    // ==========================================
    public function getDownline(Request $request)
    {
        // 1. Zero-trust implementation ke liye global context use kar rahe hain
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // 2. Agar parent_id request me aayi hai, to uske bacchon ko layenge
        // Agar nahi aayi (first load), to login member ko root manenge
        $parentId = $request->input('parent_id');
        
        if (empty($parentId)) {
            $parentId = $user->member_id ?? $context->profile_id;
        }

        // 3. Database query: Direct children fetch karna
        // withCount('children') se hume pata chalega ki is child ke aage '+' lagana hai ya nahi
        $downline = \App\Models\Member::where('sponsor_id', $parentId)
            ->withCount('children') // Ye Step 1 wale relation ko use karke count nikalega
            ->get([
                'id', 
                'member_id', 
                'member_name', 
                'status',       // active, inactive, pending aadi ke liye
                'mem_status',   // On Board, Transferred aadi ke liye
                'sponsor_id'
            ]);

        // Optional: Color code class backend se hi assign kar sakte hain
        $downline->map(function ($member) {
            $colorClass = 'text-secondary'; // default
            
            if (strtolower($member->status) === 'active') {
                $colorClass = 'text-success'; 
            } elseif (strtolower($member->status) === 'pending') {
                $colorClass = 'text-warning'; 
            } elseif (strtolower($member->status) === 'inactive' || strtolower($member->status) === 'rejected') {
                $colorClass = 'text-danger'; 
            }
            
            $member->color_class = $colorClass;
            return $member;
        });

        return response()->json([
            'status' => 'success',
            'data' => $downline
        ]);
    }

}
