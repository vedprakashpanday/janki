<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\Branch;
use Illuminate\Http\Request;

class LandownerController extends Controller
{
    public function index()
    {
        // Agent ka naam dikhane ke liye aap agent model ko link kar sakte hain, abhi branch bhej rahe hain
        $landowners = Landowner::with('branch')->orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $landowners]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'land_owner_name' => 'required',
            'mobile1' => 'required'
        ]);

        $data = $request->except(['_token']);

        // 1. Generate IDs (LO = Land Owner, LI = Land ID)
        $branch = Branch::findOrFail($request->branch_id);
        $branchParts = explode('/', $branch->branch_id); 
        $stateCode = $branchParts[1] ?? 'ST';
        $distCode  = $branchParts[2] ?? 'DIST';
        $year = date('Y');

        $lastLO = Landowner::where('branch_id', $branch->id)->orderBy('id', 'desc')->first();
        $nextSeq = 1;
        if ($lastLO && $lastLO->land_owner_id) {
            $lastIdParts = explode('/', $lastLO->land_owner_id);
            $nextSeq = ((int) ($lastIdParts[3] ?? 0)) + 1;
        }

        $sequence = str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        $data['land_owner_id'] = "LO/{$stateCode}/{$distCode}/{$sequence}/{$year}";
        $data['land_id']       = "LI/{$stateCode}/{$distCode}/{$sequence}/{$year}";

        // 2. File Uploads (20 Docs)
        $fileFields = [
            'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'passport_photo', 'sign',
            'khatiyaan_pdf', 'jamabandi_pdf', 'lo_agreement_pdf', 'registry_deed_pdf', 'link_deed_pdf', 'final_deed_pdf', 'other_pdf',
            'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_passport_pdf', 'nom_passport_photo', 'nom_other_pdf'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/landowners'), $filename);
                $data[$field] = 'uploads/landowners/' . $filename;
            }
        }

        $landowner = Landowner::create($data);
        return response()->json(['status' => 'success', 'message' => "Landowner saved! ID: {$landowner->land_owner_id}"]);
    }

    public function show($id)
    {
        return response()->json(['status' => 'success', 'data' => Landowner::with('branch')->findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $landowner = Landowner::findOrFail($id);
        $data = $request->except(['_token', 'land_owner_id', 'land_id', '_method']);

        $fileFields = [ /* Same 20 files */
            'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'passport_photo', 'sign',
            'khatiyaan_pdf', 'jamabandi_pdf', 'lo_agreement_pdf', 'registry_deed_pdf', 'link_deed_pdf', 'final_deed_pdf', 'other_pdf',
            'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_passport_pdf', 'nom_passport_photo', 'nom_other_pdf'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/landowners'), $filename);
                $data[$field] = 'uploads/landowners/' . $filename;
            }
        }

        $landowner->update($data);
        return response()->json(['status' => 'success', 'message' => 'Landowner updated successfully']);
    }

    public function destroy($id)
    {
        Landowner::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}