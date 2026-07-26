<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Branch;

class IdCardController extends Controller
{
    // ==========================================
    // 1. FILTERED STAFF LIST API (For UI Dropdowns)
    // ==========================================
    public function getStaffList(\Illuminate\Http\Request $request)
    {
        $context = $this->getGlobalContext();

        $empQuery = DB::table('adm_regist')
            ->select('member_id as id', 'full_name as name', DB::raw("'Employee' as type"))
            ->where('emp_status', 'active');

        $memQuery = DB::table('members')
            ->select('member_id as id', 'member_name as name', DB::raw("'Member' as type"))
            ->where('status', 'active');

        // 🔥 1. COMPANY FILTER
        if ($request->filled('company_id')) {
            $empQuery->where('company_id', $request->company_id);
            $memQuery->where('company_id', $request->company_id);
        }

        // 🔥 2. BRANCH FILTER
        if ($request->filled('branch_id')) {
            if ($request->branch_id === 'HO') {
                $empQuery->where(function($q) { $q->whereNull('branch_id')->orWhere('branch_id', ''); });
                $memQuery->where(function($q) { $q->whereNull('branch_id')->orWhere('branch_id', ''); });
            } else {
                $empQuery->where('branch_id', $request->branch_id);
                $memQuery->where('branch_id', $request->branch_id);
            }
        }

        // 🔥 3. DEPARTMENT FILTER
        if ($request->filled('department_id')) {
            $empQuery->where('department_id', $request->department_id);
            $memQuery->where('department_id', $request->department_id); // Ab members bhi properly filter honge
        }

        // 🔥 4. DESIGNATION FILTER
        if ($request->filled('designation_id')) {
            $empQuery->where('designation_id', $request->designation_id);
            $memQuery->where('designation_id', $request->designation_id); // Ab associate designations par associate aayenge
        }
        // ==========================================
        // 🛡️ SECURITY SCOPING LOGIC (God Mode Check)
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodMode = false;
        
        // CEO aur Developers ko bypass karne ka pakka intezaam
        if ($user && (in_array($user->email ?? '', $developerEmails) || class_basename($user) === 'SuperAdmin' || $context->is_god)) {
            $isGodMode = true;
        }

        if (!$isGodMode) {
            // Agar normal user hai to sirf uski company/branch lock hogi
            $empQuery->where('company_id', $context->company_id);
            $memQuery->where('company_id', $context->company_id);

            if (!$context->is_director && !empty($context->branch_id)) {
                $empQuery->where('branch_id', $context->branch_id);
                $memQuery->where('branch_id', $context->branch_id);
            }
        }
        // ==========================================

        $employees = $empQuery->get();
        $members = $memQuery->get();

        $staff = $employees->merge($members)->sortBy('name')->values();

        return response()->json(['status' => 'success', 'data' => $staff]);
    }


   // ==========================================
    // 2. DYNAMIC PRINT PREVIEW RENDERER
    // ==========================================
    public function printPreview(\Illuminate\Http\Request $request, $type)
    {
        $id = $request->query('member_id');
        if (!$id) abort(404, 'ID is missing in the request!');

        $user = DB::table('adm_regist')->where('member_id', $id)->first();
        $isEmployee = true;

        if (!$user) {
            $user = DB::table('members')->where('member_id', $id)->first();
            $isEmployee = false;
        }

        if (!$user) abort(404, 'Staff Member Not Found!');

        // 🛡️ SECURITY & OWNERSHIP CHECK
        $authUser = auth('sanctum')->user() ?? auth()->user(); 
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodMode = false;
        if ($authUser && (in_array($authUser->email ?? '', $developerEmails) || class_basename($authUser) === 'SuperAdmin')) {
            $isGodMode = true;
        }
        if (!$isGodMode && $authUser && !$authUser->hasRole(['CEO', 'Director'])) {
            if (isset($user->company_id) && $user->company_id != $authUser->company_id) {
                abort(403, 'Strict Security: You are not authorized to view or print Cards for other Companies.');
            }
        }

        // 🏢 FETCH COMPANY DATA (Hamesha Head Office ka address lagega)
        $company = \App\Models\Company::find($user->company_id ?? 1);

        // 🖋️ FETCH CEO SIGNATURE (From super_admins table)
        $superAdmin = \App\Models\SuperAdmin::where('status', 'active')->first();
        $ceoSignature = $superAdmin ? $superAdmin->signature_photo : null;

        // 🧑‍💼 DYNAMIC EMPLOYEE / MEMBER LOGIC
        $isEmpCode = strpos($id, '-A/') !== false;
        $typeText = $isEmpCode ? 'Employee' : 'Member';
        $codeLabel = $isEmpCode ? 'Employee Code' : 'Member Code';

        $userArr = (array) $user;

        // 🔥 NAYA FIX: Fetch Designation Name using designation_id
        $designationName = $isEmployee ? 'Employee' : 'Member'; // Default fallback
        
        if (!empty($userArr['designation_id'])) {
            // ID se table hit karke real name nikal rahe hain
            $fetchedDesignation = DB::table('designations')->where('id', $userArr['designation_id'])->value('designation_name');
            if ($fetchedDesignation) {
                $designationName = $fetchedDesignation;
            }
        } elseif (!empty($userArr['designation'])) {
            // Agar designation_id null hai but purana text designation majood hai toh usko fallback bana do
            $designationName = $userArr['designation'];
        }
        
        $data = [
            'id' => $id,
            'name' => $isEmployee ? ($userArr['full_name'] ?? '') : ($userArr['member_name'] ?? $userArr['full_name'] ?? ''),
            'father_name' => $isEmployee ? ($userArr['father_spouse_name'] ?? '') : ($userArr['so_do_name'] ?? $userArr['father_spouse_name'] ?? ''),
            'designation' => $designationName, // 🔥 Updated Variable Passed Here
            'dob' => $isEmployee ? ($userArr['dob'] ?? '') : ($userArr['dob'] ?? $userArr['date_of_birth'] ?? ''),
            'aadhar' => $isEmployee ? ($userArr['aadhar_no'] ?? '') : ($userArr['aadhar_no'] ?? $userArr['aadhar_number'] ?? ''),
            'blood_group' => $userArr['blood_group'] ?? 'N/A',
            'photo' => $userArr['passport_photo'] ?? $userArr['photo'] ?? null,
            'mobile' => $isEmployee ? ($userArr['contact_no'] ?? $userArr['mobile'] ?? '') : ($userArr['mobile'] ?? $userArr['m_num'] ?? $userArr['contact_no'] ?? ''),
            'email' => $isEmployee ? ($userArr['email'] ?? '') : ($userArr['email'] ?? $userArr['m_email'] ?? ''),
            
            // 🔥 DYNAMIC LABELS
            'type_text' => $typeText,
            'code_label' => $codeLabel,

            // 🔥 DYNAMIC COMPANY DETAILS (Always Head Office)
            'company_name' => $company ? $company->company_name : 'N/A',
            'company_logo' => ($company && $company->company_logo) ? asset($company->company_logo) : asset('uploads/harihomes1-logo.png'),
            'cin_no' => $company ? $company->cin_no : 'N/A',
            'iso_no' => $company ? $company->iso_no : 'iso_no',
            'company_address' => $company ? $company->address : 'N/A',
            'company_phone' => $company ? $company->phone : '',
            'company_email' => $company ? $company->email : 'N/A',
            // 👇 YE 2 LINES ADD KARNI HAIN ERROR FIX KARNE KE LIYE 👇
            'is_ho' => true, 
            'branch_address' => $company ? $company->address : 'N/A',
            
            // 🔥 CEO SIGNATURE
            'signature' => $ceoSignature ? asset($ceoSignature) : null,
        ];

        $data['dob'] = ($data['dob'] && $data['dob'] != '0000-00-00') ? date('d-m-Y', strtotime($data['dob'])) : '-';
        $data['photo_url'] = !empty($data['photo']) ? asset($data['photo']) : asset("image/default-user.png");
        $data['first_letter'] = !empty($data['name']) ? strtoupper(substr(trim($data['name']), 0, 1)) : 'A';

        if ($type === 'id_card') return view('admin.prints.id_cards', compact('data'));
        if ($type === 'visiting_normal') return view('admin.prints.visiting_normal', compact('data'));
        if ($type === 'visiting_premium') return view('admin.prints.visiting_card2', compact('data'));

        abort(404);
    }
}