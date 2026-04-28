<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Branch;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::with('branch')->orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $members]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'member_name' => 'required',
            'mobile' => 'required',
            'doj' => 'required|date'
        ]);

        $data = $request->except(['_token']);

        // 1. Branch ID Logic (e.g., ABM/BR/DBG1/01/2026)
        $branch = Branch::findOrFail($request->branch_id);
        $branchParts = explode('/', $branch->branch_id); // JV/BR/DBG1/2026
        
        $stateCode = $branchParts[1] ?? 'ST';
        $distCode  = $branchParts[2] ?? 'DIST';
        $year = date('Y', strtotime($request->doj));

        $lastMember = Member::where('branch_id', $branch->id)->orderBy('id', 'desc')->first();
        if ($lastMember && $lastMember->member_id) {
            $lastIdParts = explode('/', $lastMember->member_id);
            // Array format: [ABM, BR, DBG1, 01, 2026] -> Index 3 is Sequence
            $lastSeq = (int) ($lastIdParts[3] ?? 0); 
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        $sequence = str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        $data['member_id'] = "ABM/{$stateCode}/{$distCode}/{$sequence}/{$year}";

        // 2. Password Generation: Name(3) + @ + Aadhar(4)
        $firstName = explode(' ', $request->member_name)[0];
        $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
        $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_number ?? '0000'), -4);
        $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

        // 3. File Uploads (All 23 fields)
        $fileFields = [
            'aadharcard', 'pancard', 'bankpassbook', 'drivinglicense', 'passport', 'passport_photo', 'sign',
            'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc',
            'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport',
            'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate',
            'nom_pgcertificate', 'nom_otherdoc'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/members'), $filename);
                $data[$field] = 'uploads/members/' . $filename;
            }
        }

        $member = Member::create($data);

        return response()->json([
            'status' => 'success', 
            'message' => "Member saved! ID: {$member->member_id} | Pass: {$member->password}"
        ]);
    }

    public function show($id)
    {
        return response()->json(['status' => 'success', 'data' => Member::with('branch')->findOrFail($id)]);
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'branch_id' => 'required|exists:branches,id',
        'member_name' => 'required',
        'mobile' => 'required',
        'doj' => 'required|date'
    ]);

    $member = Member::findOrFail($id);
    $data = $request->except(['_token', 'member_id', '_method']); // member_id ko update se roka gaya hai 
    
    if (empty($data['password'])) {
        unset($data['password']);
    }

    // File fields array aapke controller ke hisaab se 
    $fileFields = [ 
        'aadharcard', 'pancard', 'bankpassbook', 'drivinglicense', 'passport', 'passport_photo', 'sign',
        'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc',
        'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport',
        'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate',
        'nom_pgcertificate', 'nom_otherdoc'
    ];
    
    foreach ($fileFields as $field) {
        if ($request->hasFile($field)) {
            /** @var \Illuminate\Http\UploadedFile $file */
            $file = $request->file($field);
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/members'), $filename);
            $data[$field] = 'uploads/members/' . $filename;
        }
    }

    $member->update($data);
    return response()->json(['status' => 'success', 'message' => 'Member updated']);
}

    public function destroy($id)
    {
        Member::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }
}