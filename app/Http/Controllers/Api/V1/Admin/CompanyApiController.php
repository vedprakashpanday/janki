<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

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
            'company_code' => 'required|string|max:10|unique:companies,company_code', // Validation add kiya
        ]);

        $company = Company::create([
            'company_name' => $request->company_name,
            'company_code' => strtoupper($request->company_code), // Always uppercase
            'parent_id' => $request->parent_id ?: null,
            'phone' => $request->phone,
            'email' => $request->email,
            'state' => $request->state,
            'district' => $request->district,
            'address' => $request->address,
            'gst_no' => $request->gst_no,
            'status' => $request->status ?? 'active'
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
        ]);

        if ($request->parent_id == $id) {
            return response()->json(['status' => 'error', 'message' => 'A company cannot be its own parent!'], 400);
        }

        // UPDATE HONE SE PEHLE PURANA STATUS STORE KAR LIYA
        $oldStatus = $company->status;
        $newStatus = $request->status ?? 'active';

        $company->update([
            'company_name' => $request->company_name,
            'company_code' => strtoupper($request->company_code),
            'parent_id' => $request->parent_id ?: null,
            'phone' => $request->phone,
            'email' => $request->email,
            'state' => $request->state,
            'district' => $request->district,
            'address' => $request->address,
            'gst_no' => $request->gst_no,
            'status' => $newStatus
        ]);

        // 🔥 CASCADING LOGIC: Agar Company Inactive hui hai, toh sabko Inactive kar do 🔥
        if ($oldStatus === 'active' && $newStatus === 'inactive') {
            
            // 1. Saari Sub-Companies (Children) ko inactive karein
            Company::where('parent_id', $id)->update(['status' => 'inactive']);
            
            // 2. Is Company ki saari Branches ko inactive karein
            \App\Models\Branch::where('company_id', $id)->update(['branch_status' => 'inactive']);
            
            /* 
               👇 FUTURE REFERENCE: Aage chalkar jab aap Employee aur Worker banayenge, 
               toh unka code bas yahan add karte jaana hai:
               
               \App\Models\Employee::where('company_id', $id)->update(['status' => 'inactive']);
               \App\Models\Customer::where('company_id', $id)->update(['status' => 'inactive']);
            */
        }

        // Agar Company wapas Active hui, toh hum automatically branches active nahi karte (Business Logic). 
        // User ko manual jaakar branch active karni chahiye.

        return response()->json(['status' => 'success', 'message' => 'Company Updated & Relational Status Applied!']);
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