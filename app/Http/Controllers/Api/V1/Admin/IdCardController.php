<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdCardController extends Controller
{
    // API: Dropdown me Datalist ke liye
    public function getStaffList()
    {
        // 1. Employees (adm_regist)
        $employees = DB::table('adm_regist')
            ->select('member_id as id', 'full_name as name', DB::raw("'Employee' as type"))
            ->where('emp_status', 'active')
            ->get();

        // 2. Members (members)
        $members = DB::table('members')
            ->select('member_id as id', 'member_name as name', DB::raw("'Member' as type"))
            ->where('status', 'active')
            ->get();

        $staff = $employees->merge($members)->sortBy('name')->values();

        return response()->json(['status' => 'success', 'data' => $staff]);
    }

    // WEB: Print View Render karne ke liye
    public function printPreview($type, $id)
    {
        $user = DB::table('adm_regist')->where('member_id', $id)->first();
        $isEmployee = true;

        if (!$user) {
            $user = DB::table('members')->where('member_id', $id)->first();
            $isEmployee = false;
        }

        if (!$user) {
            abort(404, 'Staff Member Not Found!');
        }

        // Safe Data Mapping (Taaki column name mismatch ka error na aaye)
        $userArr = (array) $user;
        
        $data = [
            'id' => $id,
            'name' => $isEmployee ? ($userArr['full_name'] ?? '') : ($userArr['member_name'] ?? $userArr['full_name'] ?? ''),
            'father_name' => $isEmployee ? ($userArr['father_spouse_name'] ?? '') : ($userArr['so_do_name'] ?? $userArr['father_spouse_name'] ?? ''),
            'designation' => $userArr['designation'] ?? ($isEmployee ? 'Employee' : 'Member'),
            'dob' => $isEmployee ? ($userArr['dob'] ?? '') : ($userArr['dob'] ?? $userArr['date_of_birth'] ?? ''),
            'aadhar' => $isEmployee ? ($userArr['aadhar_no'] ?? '') : ($userArr['aadhar_no'] ?? $userArr['aadhar_number'] ?? ''),
            'blood_group' => $userArr['blood_group'] ?? 'N/A',
            'photo' => $userArr['passport_photo'] ?? $userArr['photo'] ?? null,
            'mobile' => $isEmployee ? ($userArr['contact_no'] ?? $userArr['mobile'] ?? '') : ($userArr['mobile'] ?? $userArr['m_num'] ?? $userArr['contact_no'] ?? ''),
            'email' => $isEmployee ? ($userArr['email'] ?? '') : ($userArr['email'] ?? $userArr['m_email'] ?? ''),
        ];

        // Format Date
        $data['dob'] = ($data['dob'] && $data['dob'] != '0000-00-00') ? date('d-m-Y', strtotime($data['dob'])) : '-';

        // Photo aur Avatar logic
        $data['photo_url'] = !empty($data['photo']) ? asset("uploads/passport_photo/" . $data['photo']) : asset("image/default-user.png");
        $data['first_letter'] = !empty($data['name']) ? strtoupper(substr(trim($data['name']), 0, 1)) : 'A';

        // 👇 YAHAN CHECK KAREIN: compact('data') miss nahi hona chahiye
        if ($type === 'id_card') return view('admin.prints.id_cards', compact('data'));
        if ($type === 'visiting_normal') return view('admin.prints.visiting_normal', compact('data'));
        if ($type === 'visiting_premium') return view('admin.prints.visiting_card2', compact('data'));

        abort(404);
    }
}