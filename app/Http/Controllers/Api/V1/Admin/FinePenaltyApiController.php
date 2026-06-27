<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinePenalty;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;

class FinePenaltyApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = FinePenalty::with(['employee', 'company']);

        if ($context->is_employee) {
            $query->where('employee_id', $context->profile_id);
        } elseif ($context->is_director && $context->company_id) {
            $query->where('company_id', $context->company_id);
        }

        return response()->json($query->orderBy('id', 'desc')->get());
    }

   
// 2. Store Method Update (Save Multiple IDs)
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        foreach ($request->employee_ids as $empId) {
            FinePenalty::create([
                'company_id' => $request->company_id,
                'branch_id' => $request->branch_id[0] ?? null, 
                'department_id' => $request->department_id[0] ?? null,
                'designation_id' => $request->designation_id[0] ?? null,
                'employee_id' => $empId,
                'fine_rupees' => $request->fine_rupees,
                'fine_days' => $request->fine_days,
                'penalty_rupees' => $request->penalty_rupees,
                'penalty_days' => $request->penalty_days,
                'date' => $request->date,
                'description' => $request->description,
                'proof_media_id' => $request->proof_media_ids, // 🔥 Yahan string format (1,2,3) aayega
                'status' => 'Pending',
                'created_by' => $context->profile_id
            ]);
        }
        return response()->json(['message' => 'Successfully Applied']);
    }

  public function getFilteredDepartments(Request $request)
    {
        $query = Department::where('department_name', 'not like', '%Associate%');
        $companyId = (string) $request->company_id;

        $query->where(function($q) use ($companyId) {
            $q->whereJsonContains('company_ids', $companyId)
              ->orWhereJsonContains('company_ids', 'all');
        });

        $branchIds = $request->branch_ids;
        
        // Null aur empty array check
        if (!empty($branchIds) && is_array($branchIds) && $branchIds[0] != "") {
            $query->where(function($q) use ($branchIds) {
                foreach ($branchIds as $bId) {
                    if(!empty($bId)) {
                        $q->orWhereJsonContains('branch_ids', (string) $bId);
                    }
                }
            });
        } else {
            $query->whereNull('branch_ids');
        }

        return response()->json($query->get());
    }
    public function bulkDelete(Request $request)
    {
        FinePenalty::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }


   public function getFilteredDesignations(Request $request)
    {
        $deptIds = $request->department_ids;
        // Agar array empty hai ya null aaya hai, to turant blank array return kardo
        if (empty($deptIds) || !is_array($deptIds)) {
            return response()->json([]);
        }
        
        $query = Designation::whereIn('department_id', $deptIds);
        return response()->json($query->get());
    }

    public function getFilteredEmployees(Request $request)
    {
        $desigIds = $request->designation_ids;
        // Agar array empty hai ya null aaya hai, to turant blank array return kardo
        if (empty($desigIds) || !is_array($desigIds)) {
            return response()->json([]);
        }

        $query = Employee::whereIn('designation_id', $desigIds);
        return response()->json($query->select('id', 'full_name', 'member_id')->get());
    }

    // Append these methods inside FinePenaltyApiController class

    public function approve($id)
    {
        $fine = FinePenalty::findOrFail($id);
        $fine->update(['status' => 'Approved']);
        return response()->json(['message' => 'Fine/Penalty Approved Successfully']);
    }

    public function reject($id)
    {
        $fine = FinePenalty::findOrFail($id);
        $fine->update(['status' => 'Rejected']);
        return response()->json(['message' => 'Fine/Penalty Rejected']);
    }

    public function updateRemark(Request $request, $id)
    {
        $request->validate(['description' => 'required']);
        $fine = FinePenalty::findOrFail($id);
        
        // Purana description aur naya remark concatenate kar sakte hain ya overwrite.
        $newDescription = $fine->description . "<br><b>Remark:</b> " . $request->description;
        $fine->update(['description' => $newDescription]);
        
        return response()->json(['message' => 'Remark Updated']);
    }

    public function printPreview($id)
    {
        // Ye function Web route /fine-penalties/print/{id} se call hoga
        $fine = FinePenalty::with(['employee.designation', 'employee.department', 'company'])->findOrFail($id);
        $company = $fine->company; // PrintHeader component ke liye
        
        // Aap ek naya blade view banayenge resources/views/admin/fine_penalties/print.blade.php
        return view('admin.fine_penalties.print', compact('fine', 'company'));
    }

    public function show($id)
    {
        // Edit form me data bharne ke liye
        $fine = FinePenalty::with(['employee', 'company'])->findOrFail($id);
        return response()->json($fine);
    }

    public function update(Request $request, $id)
    {
        $fine = FinePenalty::findOrFail($id);
        
        // Update only relevant fields (Company, Employee wagerah update nahi karne denge taaki record safe rahe)
        $fine->update([
            'fine_rupees' => $request->fine_rupees,
            'fine_days' => $request->fine_days,
            'penalty_rupees' => $request->penalty_rupees,
            'penalty_days' => $request->penalty_days,
            'date' => $request->date,
            'description' => $request->description,
        ]);

        return response()->json(['message' => 'Record Updated Successfully']);
    }
}