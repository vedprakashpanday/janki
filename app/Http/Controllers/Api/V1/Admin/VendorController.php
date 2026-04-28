<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Branch;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::with('branch')->orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $vendors]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required',
            'contact_no' => 'required',
        ]);

        $data = $request->except(['_token']);

        // 1. VD/BR/DBG1/01/2026 Logic
        $branch = Branch::findOrFail($request->branch_id);
        $branchParts = explode('/', $branch->branch_id); 
        $stateCode = $branchParts[1] ?? 'ST';
        $distCode  = $branchParts[2] ?? 'DIST';
        $year = date('Y');

        $lastVendor = Vendor::where('branch_id', $branch->id)->orderBy('id', 'desc')->first();
        $nextSeq = 1;
        if ($lastVendor && $lastVendor->vendor_id) {
            $lastIdParts = explode('/', $lastVendor->vendor_id);
            $nextSeq = ((int) ($lastIdParts[3] ?? 0)) + 1;
        }

        $sequence = str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        $data['vendor_id'] = "VD/{$stateCode}/{$distCode}/{$sequence}/{$year}";

        // 2. Password Generation (Name@Aadhar)
        $firstName = explode(' ', $request->full_name)[0];
        $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
        $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_no ?? '0000'), -4);
        $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

        // 3. File Uploads
        $fileFields = [
            'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'other_pdf',
            'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_other_pdf'
        ];
        
        $imageFields = ['passport_photo', 'nom_passport_photo'];

        // PDF Files handle
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/vendors'), $filename);
                $data[$field] = 'uploads/vendors/' . $filename;
            }
        }

        // IMAGE Files Handle (Global Converter Here)
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                
                // BHAIA, YAHAN AAPKA GLOBAL IMAGE CONVERTER LAGEGA!
                // Example: $convertedPath = GlobalImageConverter::convert($file, 'uploads/vendors/');
                // $data[$field] = $convertedPath;
                
                // Abhi default move kar raha hu:
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/vendors/images'), $filename);
                $data[$field] = 'uploads/vendors/images/' . $filename;
            }
        }

        if(($data['vendor_status'] ?? 'active') == 'active'){
            $data['d_o_l'] = null;
            $data['leaving_remarks'] = null;
        }

        $vendor = Vendor::create($data);
        return response()->json(['status' => 'success', 'message' => "Vendor saved! ID: {$vendor->vendor_id}"]);
    }

    public function show($id)
    {
        return response()->json(['status' => 'success', 'data' => Vendor::with('branch')->findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $data = $request->except(['_token', 'vendor_id', '_method']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $fileFields = ['aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'other_pdf', 'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_other_pdf'];
        $imageFields = ['passport_photo', 'nom_passport_photo'];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/vendors'), $filename);
                $data[$field] = 'uploads/vendors/' . $filename;
            }
        }

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                // SAME GLOBAL CONVERTER LOGIC YAHAN BHI LAGEGA
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/vendors/images'), $filename);
                $data[$field] = 'uploads/vendors/images/' . $filename;
            }
        }

        if(($data['vendor_status'] ?? 'active') == 'active'){
            $data['d_o_l'] = null;
            $data['leaving_remarks'] = null;
        }

        $vendor->update($data);
        return response()->json(['status' => 'success', 'message' => 'Vendor updated successfully']);
    }

    public function destroy($id)
    {
        Vendor::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}