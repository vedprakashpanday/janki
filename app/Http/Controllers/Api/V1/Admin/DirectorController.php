<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Director;
use Illuminate\Http\Request;
use App\Services\MediaConverterService;
use Illuminate\Support\Facades\File;

class DirectorController extends Controller
{
    public function index(Request $request)
    {
        // 🔥 NAYA: with('company') add kiya
        $query = Director::query(); 

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('director_id', 'LIKE', "%{$search}%")
                  ->orWhere('contact_no', 'LIKE', "%{$search}%");
        }

        $totalData = Director::count();
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $directors = $query->orderBy('id', 'desc')->get();

      

        return response()->json([
            "draw" => intval($request->input('draw', 0)),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $directors
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
            return response()->json(['status' => 'error', 'message' => 'Strict Restriction: Only Master Admins can create new Director Profiles.'], 403);
        }
        // ==========================================

        $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:15',
            'aadhar_no' => 'required|string|max:20',
            'email' => 'nullable|email|unique:directors,email',
            'password' => 'required|string|min:6'
            
        ]);

        $data = $request->except(['_method', 'password']);
        $data['director_id'] = 'DIR-' . mt_rand(100000, 999999);
        $data['password'] = $request->password;

        $this->handleFiles($request, $data);

        Director::create($data);

        return response()->json(['status' => 'success', 'message' => 'Director Profile Provisioned Successfully!']);
    }

    public function show($id)
    {
        $director = Director::find($id);
        if (!$director) return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);
        return response()->json(['status' => 'success', 'data' => $director]);
    }

    public function update(Request $request, $id)
    {
        // ==========================================
        // 🛡️ STRICT GOD-MODE CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            return response()->json(['status' => 'error', 'message' => 'Strict Restriction: Only Master Admins can modify Director Profiles.'], 403);
        }
        // ==========================================



        $director = Director::find($id);
        if (!$director) return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:15',
            'aadhar_no' => 'required|string|max:20',
           
            'email' => 'nullable|email|unique:directors,email,' . $id,
        ]);

        $data = $request->except(['_method', 'password']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $this->handleFiles($request, $data, $director);

        $director->update($data);

        return response()->json(['status' => 'success', 'message' => 'Director Profile Updated Successfully!']);
    }

    public function destroy($id)
    {
        // ==========================================
        // 🛡️ STRICT GOD-MODE CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            return response()->json(['status' => 'error', 'message' => 'Strict Restriction: Only Master Admins can delete Director Profiles.'], 403);
        }
        // ==========================================

        $director = Director::find($id);
        if ($director) {
            $fileFields = array_merge($this->getImageFields(), $this->getPdfFields());
            foreach ($fileFields as $field) {
                if ($director->$field && File::exists(public_path($director->$field))) {
                    File::delete(public_path($director->$field));
                }
            }
            $director->delete();
        }
        return response()->json(['status' => 'success', 'message' => 'Director Profile Purged Successfully!']);
    }

    private function handleFiles($request, &$data, $existingModel = null)
    {
        $converter = new MediaConverterService();
        $uploadPath = 'uploads/directors/';

        if (!File::exists(public_path($uploadPath))) {
            File::makeDirectory(public_path($uploadPath), 0777, true, true);
        }

        foreach ($this->getImageFields() as $field) {
            if ($request->hasFile($field)) {
                $media = $converter->uploadAndConvert($request->file($field));
                if ($media) {
                    $this->deleteOldFile($existingModel, $field);
                    $data[$field] = $media->file_path;
                }
            }
        }

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


    public function getActiveDirectors()
{
    // Sirf active directors aur unki basic details
    $directors = Director::where('status', 'active')
                         ->select('id', 'full_name', 'director_id')
                         ->get();
                         
    return response()->json(['status' => 'success', 'data' => $directors]);
}



}
