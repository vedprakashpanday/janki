<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use App\Services\MediaConverterService;
use Illuminate\Support\Facades\File;

class SuperAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = SuperAdmin::query();

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('full_name', 'LIKE', "%{$search}%")
                ->orWhere('ceo_id', 'LIKE', "%{$search}%")
                ->orWhere('contact_no', 'LIKE', "%{$search}%");
        }

        $totalData = SuperAdmin::count();
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $admins = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw', 0)),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $admins
        ]);
    }

    public function store(Request $request)
    {

    // ==========================================
        // 🛡️ STRICT GOD-MODE CHECK (Master Profile Protection)
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            return response()->json(['status' => 'error', 'message' => 'Strict Restriction: Only Master Developers can create new Super Admin (CEO) Profiles.'], 403);
        }
        // ==========================================


        $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:15',
            'aadhar_no' => 'required|string|max:20',
            'email' => 'nullable|email|unique:super_admins,email',
            'password' => 'required|string|min:6'
        ]);

        $data = $request->except(['_method', 'password']);

        // Generate Unique CEO ID (Format: CEO-XXXXXX)
        $data['ceo_id'] = 'CEO-' . mt_rand(100000, 999999);

        // Auto-hash hoga model event (boot) ke through jo humne model me likha tha
        $data['password'] = $request->password;

        // Handle Files
        $this->handleFiles($request, $data);

        SuperAdmin::create($data);

        return response()->json(['status' => 'success', 'message' => 'Super Admin (CEO) Registered Successfully!']);
    }

    public function show($id)
    {
        $admin = SuperAdmin::find($id);
        if (!$admin) return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);
        return response()->json(['status' => 'success', 'data' => $admin]);
    }

    public function update(Request $request, $id)
    {


    // ==========================================
        // 🛡️ STRICT GOD-MODE CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            return response()->json(['status' => 'error', 'message' => 'Strict Restriction: Only Master Developers can modify Super Admin (CEO) Profiles.'], 403);
        }
        // ==========================================


        $admin = SuperAdmin::find($id);
        if (!$admin) return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:15',
            'aadhar_no' => 'required|string|max:20',
            'email' => 'nullable|email|unique:super_admins,email,' . $id,
        ]);

        $data = $request->except(['_method', 'password']);

        // Agar password change karna ho
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        // Handle Files Update
        $this->handleFiles($request, $data, $admin);

        $admin->update($data);

        return response()->json(['status' => 'success', 'message' => 'Super Admin (CEO) Updated Successfully!']);
    }

    public function destroy($id)
    {

    // ==========================================
        // 🛡️ STRICT GOD-MODE CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            return response()->json(['status' => 'error', 'message' => 'Strict Restriction: Only Master Developers can delete Super Admin (CEO) Profiles.'], 403);
        }
        // ==========================================

        $admin = SuperAdmin::find($id);
        if ($admin) {
            // Delete all associated files
            $fileFields = array_merge($this->getImageFields(), $this->getPdfFields());
            foreach ($fileFields as $field) {
                if ($admin->$field && File::exists(public_path($admin->$field))) {
                    File::delete(public_path($admin->$field));
                }
            }
            $admin->delete();
        }
        return response()->json(['status' => 'success', 'message' => 'Record Deleted Successfully!']);
    }

    // ==========================================
    // HELPER METHODS FOR FILE UPLOADS
    // ==========================================
    private function handleFiles($request, &$data, $existingModel = null)
    {
        $converter = new MediaConverterService();
        $uploadPath = 'uploads/super_admins/';

        if (!File::exists(public_path($uploadPath))) {
            File::makeDirectory(public_path($uploadPath), 0777, true, true);
        }

        // 1. Process Images (Compress to WebP)
        foreach ($this->getImageFields() as $field) {
            if ($request->hasFile($field)) {
                $media = $converter->uploadAndConvert($request->file($field));
                if ($media) {
                    $this->deleteOldFile($existingModel, $field);
                    $data[$field] = $media->file_path;
                }
            }
        }

        // 2. Process PDFs (Direct Upload)
        foreach ($this->getPdfFields() as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path($uploadPath), $fileName);

                $this->deleteOldFile($existingModel, $field);
                $data[$field] = $uploadPath . $fileName;
            }
        }
    }

    private function deleteOldFile($model, $field)
    {
        if ($model && $model->$field && File::exists(public_path($model->$field))) {
            File::delete(public_path($model->$field));
        }
    }

    private function getImageFields()
    {
        return ['passport_photo', 'signature_photo', 'nom_passport_photo', 'nom_signature_photo'];
    }

    private function getPdfFields()
    {
        return ['aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'residential_proof_pdf', 'landmark_doc_pdf', 'other_doc_pdf', 'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_residential_proof_pdf', 'nom_landmark_doc_pdf', 'nom_other_doc_pdf'];
    }
}
