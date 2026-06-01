<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Services\MediaConverterService; // 🔥 Aapki Service Import Kar Li

class CompanyApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::with(['parent', 'directors']);

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            // Developer ke alawa sabko sirf apni company ki details dikhengi
            $query->where('id', $user->company_id);
        }
        // ==========================================

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('company_name', 'LIKE', "%{$search}%")
                ->orWhere('company_code', 'LIKE', "%{$search}%")
                ->orWhere('state', 'LIKE', "%{$search}%");
        }

        $totalData = Company::count();
        $totalFiltered = $query->count();

        // 🔥 SAFE PAGINATION LOGIC (Baaki pages ko break hone se bachane ke liye) 🔥
        // Sirf tabhi offset aur limit lagao jab request me 'length' pass hua ho aur -1 na ho
        if ($request->has('length') && $request->input('length') != -1) {
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $query->offset($start)->limit($length);
        }

        $companies = $query->orderBy('id', 'desc')->get();

        $data = $companies->map(function ($c) {

        $directorList = $c->directors->map(function($dir) {
            return $dir->full_name . ' <small class="text-muted">(' . $dir->pivot->role . ')</small>';
        })->implode('<br>');

            return [
                'id' => $c->id,
                'company_name' => $c->company_name,
                'company_code' => '<span class="badge bg-dark">' . $c->company_code . '</span>',
                'parent_name' => $c->parent ? $c->parent->company_name : '<span class="badge bg-secondary">Master Company</span>',
                'directors_html' => $directorList ?: 'No Director',
                'state' => $c->state ?? '-',
                'district' => $c->district ?? '-',
                'status' => $c->status,
                'action' => '
                    <button onclick="printCompany(' . $c->id . ')" class="btn btn-sm btn-light border text-secondary" title="Print"><i class="fas fa-print"></i></button>
                    <button onclick="viewCompany(' . $c->id . ')" class="btn btn-sm btn-light border text-info" title="View"><i class="fas fa-eye"></i></button>
                    <button onclick="editCompany(' . $c->id . ')" class="btn btn-sm btn-light border text-success" title="Edit"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteCompany(' . $c->id . ')" class="btn btn-sm btn-light border ms-1 text-danger" title="Delete"><i class="fas fa-trash"></i></button>
                '
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw', 0)), // Default 0 if not called by DataTable
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $data
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_code' => 'required|string|max:10|unique:companies,company_code',
            'cin_no'       => 'required|string|max:255',
            'company_logo' => 'nullable|mimes:jpeg,png,jpg,gif,webp,bmp|max:5120' // Max 5MB allowed before compression
        ]);

        $logoPath = null;

        // 🔥 UNIVERSAL LOGO UPLOAD LOGIC 🔥
        if ($request->hasFile('company_logo')) {
            $converter = new MediaConverterService();
            $media = $converter->uploadAndConvert($request->file('company_logo'));

            if ($media) {
                // Media converter 'uploads/images/...' path return karega
                $logoPath = $media->file_path;
            }
        }

        $company = Company::create([
            'company_name'  => $request->company_name,
            'company_code'  => strtoupper($request->company_code),
            'company_logo'  => $logoPath, // Save WebP path to DB
            'cin_no'        => strtoupper($request->cin_no),
            'iso_no'        => $request->iso_no,
            'trademark'     => $request->trademark,
            'logo_reg_no'   => $request->logo_reg_no,
            'parent_id'     => $request->parent_id ?: null,
            'phone'         => $request->phone,
            'email'         => $request->email,
            'state'         => $request->state,
            'district'      => $request->district,
            'address'       => $request->address,
            'gst_no'        => $request->gst_no,
            'status'        => $request->status ?? 'active'
        ]);

        // Agar request me directors ka array aa raha hai
if ($request->has('directors')) {
    foreach ($request->directors as $dir) {
        // $dir = ['director_id' => 1, 'role' => 'CEO']
        $company->directors()->attach($dir['director_id'], ['role' => $dir['role']]);
    }
}


// 🔥 PIVOT DATA SAVE LOGIC 🔥
    if ($request->has('director_assignments')) {
        // Data format: [{"director_id": 1, "role": "CEO"}, {"director_id": 2, "role": "Director"}]
        $directors = json_decode($request->director_assignments, true);
        foreach ($directors as $dir) {
            $company->directors()->attach($dir['director_id'], ['role' => $dir['role']]);
        }
    }



        return response()->json(['status' => 'success', 'message' => 'Company Created Successfully!']);
    }

    public function show($id)
    {
        $company = Company::with(['parent', 'directors'])->find($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            if ($company->id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You can only access your own company.'], 403);
            }
        }

        if (!$company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $company]);
    }

    public function update(Request $request, $id)
    {
        $company = Company::find($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            if ($company->id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You can only access your own company.'], 403);
            }
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_code' => 'required|string|max:10|unique:companies,company_code,' . $id,
            'cin_no'       => 'required|string|max:255',
            'company_logo' => 'nullable|mimes:jpeg,png,jpg,gif,webp,bmp|max:5120'
        ]);

        if ($request->parent_id == $id) {
            return response()->json(['status' => 'error', 'message' => 'A company cannot be its own parent!'], 400);
        }

        $logoPath = $company->company_logo;

        // 🔥 UNIVERSAL LOGO UPDATE LOGIC 🔥
        if ($request->hasFile('company_logo')) {
            $converter = new MediaConverterService();
            $media = $converter->uploadAndConvert($request->file('company_logo'));

            if ($media) {
                // Purani image ko delete kar do taaki space bache
                if ($logoPath && File::exists(public_path($logoPath))) {
                    File::delete(public_path($logoPath));
                }
                $logoPath = $media->file_path; // Naya WebP path
            }
        } elseif ($request->remove_logo_flag == '1') {
            // Agar user ne Cut (X) button dabaya hai, toh image delete kar do
            if ($logoPath && File::exists(public_path($logoPath))) {
                File::delete(public_path($logoPath));
            }
            $logoPath = null;
        }

        $oldStatus = $company->status;
        $newStatus = $request->status ?? 'active';

        $company->update([
            'company_name'  => $request->company_name,
            'company_code'  => strtoupper($request->company_code),
            'cin_no'        => strtoupper($request->cin_no),
            'iso_no'        => $request->iso_no,
            'trademark'     => $request->trademark,
            'logo_reg_no'   => $request->logo_reg_no,
            'parent_id'     => $request->parent_id ?: null,
            'phone'         => $request->phone,
            'email'         => $request->email,
            'state'         => $request->state,
            'district'      => $request->district,
            'address'       => $request->address,
            'gst_no'        => $request->gst_no,
            'status'        => $newStatus,
            'company_logo'  => $logoPath,
        ]);

        if ($request->has('directors')) {
    $syncData = [];
    foreach ($request->directors as $dir) {
        $syncData[$dir['director_id']] = ['role' => $dir['role']];
    }
    $company->directors()->sync($syncData);
}

        if ($oldStatus === 'active' && $newStatus === 'inactive') {
            Company::where('parent_id', $id)->update(['status' => 'inactive']);
            \App\Models\Branch::where('company_id', $id)->update(['branch_status' => 'inactive']);
        }

        return response()->json(['status' => 'success', 'message' => 'Company Updated Successfully!']);
    }

    public function destroy($id)
    {
        Company::destroy($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            if ($company->id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You can only access your own company.'], 403);
            }
        }


        return response()->json(['status' => 'success', 'message' => 'Company Deleted Successfully']);
    }

   public function getActiveCompanies()
    {
        $query = Company::where('status', 'active');

        // 🛡️ DATA FILTER LOGIC
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            $query->where('id', $user->company_id);
        }

        $companies = $query->get();
        return response()->json(['status' => 'success', 'data' => $companies]);
    }
}
