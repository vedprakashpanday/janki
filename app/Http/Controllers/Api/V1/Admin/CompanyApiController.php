<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CompanyApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::with('parent');

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('company_code', 'LIKE', "%{$search}%")
                  ->orWhere('state', 'LIKE', "%{$search}%");
        }

        $totalData = Company::count();
        $totalFiltered = $query->count();
        
        $companies = $query->offset($request->input('start'))
                           ->limit($request->input('length'))
                           ->orderBy('id', 'desc')
                           ->get();

        $data = $companies->map(function($c) {
            return [
                'id' => $c->id,
                'company_name' => $c->company_name,
                'company_code' => '<span class="badge bg-dark">'.$c->company_code.'</span>', // Prefix dikhane ke liye
                'parent_name' => $c->parent ? $c->parent->company_name : '<span class="badge bg-secondary">Master Company</span>',
                'state' => $c->state ?? '-',
                'district' => $c->district ?? '-',
                'status' => $c->status,
                'action' => '
                    <button onclick="viewCompany('.$c->id.')" class="btn btn-sm btn-light border text-info" title="View"><i class="fas fa-eye"></i></button>
                    <button onclick="editCompany('.$c->id.')" class="btn btn-sm btn-light border text-success" title="Edit"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteCompany('.$c->id.')" class="btn btn-sm btn-light border ms-1 text-danger" title="Delete"><i class="fas fa-trash"></i></button>
                '
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
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
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048' // Max 2MB
        ]);

        $logoPath = null;

        // 🔥 LOGO UPLOAD LOGIC 🔥
        if ($request->hasFile('company_logo')) {
            $companyCode = strtoupper($request->company_code);
            $folderPath = public_path('company_logos/' . $companyCode);

            // Check agar folder nahi hai toh banayein + Permission dein (0775)
            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0775, true, true);
            }

            $file = $request->file('company_logo');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            
            // move_uploaded_file ka Laravel equivalent
            $file->move($folderPath, $fileName);
            
            $logoPath = 'company_logos/' . $companyCode . '/' . $fileName;
        }

        Company::create([
            'company_name'  => $request->company_name,
            'company_code'  => strtoupper($request->company_code),
            'company_logo'  => $logoPath, // Save path to DB
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

        return response()->json(['status' => 'success', 'message' => 'Company Created Successfully!']);
    }


public function show($id)
    {
        // Yahan with('parent') lagana zaroori tha taaki parent company ka naam aa sake
        $company = Company::with('parent')->find($id);
        
        if (!$company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $company]);
    }

// 4. UPDATE RECORD
public function update(Request $request, $id)
    {
        $company = Company::find($id);
        
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_code' => 'required|string|max:10|unique:companies,company_code,' . $id,
            'cin_no'       => 'required|string|max:255',
            'company_logo' => 'nullable|mimes:jpeg,png,jpg,gif,webp,svg|max:2048'
        ]);

        $logoPath = $company->company_logo; // Pehle purana path store kar liya

        // 🔥 LOGO UPDATE LOGIC 🔥
        if ($request->hasFile('company_logo')) {
            $companyCode = strtoupper($request->company_code);
            $folderPath = public_path('company_logos/' . $companyCode);

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0775, true, true);
            }

            $file = $request->file('company_logo');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move($folderPath, $fileName);
            
            // Purani image ko delete kar sakte hain yahan if you want
            if ($logoPath && File::exists(public_path($logoPath))) {
                File::delete(public_path($logoPath));
            }

            $logoPath = 'company_logos/' . $companyCode . '/' . $fileName;
        }

        if ($request->parent_id == $id) {
            return response()->json(['status' => 'error', 'message' => 'A company cannot be its own parent!'], 400);
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
            'status'        => $newStatus  ,
            'company_logo'  => $logoPath,      ]);

        if ($oldStatus === 'active' && $newStatus === 'inactive') {
            Company::where('parent_id', $id)->update(['status' => 'inactive']);
            \App\Models\Branch::where('company_id', $id)->update(['branch_status' => 'inactive']);
        }

        return response()->json(['status' => 'success', 'message' => 'Company Updated Successfully!']);
    }

    public function destroy($id)
    {
        Company::destroy($id);
        return response()->json(['status' => 'success', 'message' => 'Company Deleted Successfully']);
    }

    public function getActiveCompanies()
    {
        $companies = Company::where('status', 'active')->get();
        return response()->json(['status' => 'success', 'data' => $companies]);
    }
}