<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\TempReceipt;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\SuperAdmin;
use App\Models\Customer; // 🔥 NAYA IMPORT: Customer model ke liye
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TempReceiptApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        if (!$context) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $query = TempReceipt::with(['company', 'branch']);

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
        }

        $totalData = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => $totalData,
            "recordsFiltered" => $totalData,
            "data"            => $query->latest()->get()
        ]);
    }

    public function getFormData(Request $request)
    {
        $context = $this->getGlobalContext();
        if (!$context) return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);

        $companiesQuery = Company::where('status', 'active');
        if (!$context->is_god) $companiesQuery->where('id', $context->company_id);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'companies' => $companiesQuery->get(['id', 'company_name']),
                'phases' => \DB::table('phases')->where('status', 'active')->get(['id', 'phase_name']),
                'account_employees' => Employee::where('emp_status', 'active')->whereHas('department', function ($q) {
                    $q->where('department_name', 'LIKE', '%Account%');
                })->get(['id', 'full_name', 'member_id']),
                'ceos' => SuperAdmin::where('status', 'active')->get(['id', 'full_name', 'ceo_id']),
            ]
        ]);
    }

    // 🔥 NAYA API: Datalist ke liye Customers laane ke liye
    public function getCustomersData(Request $request)
    {
        $customers = Customer::select('id', 'customer_name', 'customer_id', 'father_name', 'spouse_name', 'customer_mobile', 'address')->get();
        return response()->json(['status' => 'success', 'data' => $customers]);
    }

    // 🔥 NAYA API: Datalist ke liye Employees (Received By) laane ke liye
    public function getEmployeesData(Request $request)
    {
        $employees = Employee::with('department:id,department_name')
            ->where('emp_status', 'active')
            ->select('id', 'full_name', 'member_id', 'department_id')
            ->get();
        return response()->json(['status' => 'success', 'data' => $employees]);
    }

    public function getBranches($company_id)
    {
        $branches = Branch::where('company_id', $company_id)->where('branch_status', 'active')->get(['id', 'branch_name']);
        return response()->json(['status' => 'success', 'data' => $branches]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer',
            'receipt_date' => 'required|date',
            'receipt_no' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'payment_mode' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'passbook_no' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $company = Company::find($request->company_id);
            $companyCode = $company ? strtoupper($company->company_code) : 'COMP';
            $suffix = (empty($request->branch_id) || $request->branch_id === 'all') ? '-H' : '-B';
            $fullReceiptNo = $companyCode . $suffix . '/RECEIPT/' . $request->receipt_no;

            if (TempReceipt::where('receipt_no', $fullReceiptNo)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'This Receipt Number ('.$fullReceiptNo.') already exists!']);
            }

            $amountDetails = $request->amount_details ?? [];
            $calculatedReceivedAmount = 0;
            foreach ($amountDetails as $detail) {
                $calculatedReceivedAmount += (float) ($detail['amount'] ?? 0);
            }

            $receipt = TempReceipt::create([
                'company_id'                 => $request->company_id,
                'branch_id'                  => $request->branch_id === 'all' ? null : $request->branch_id,
                'receipt_date'               => $request->receipt_date,
                'receipt_no'                 => $fullReceiptNo,
                'project_name'               => $request->project_name ?? 'Janki Villa',
                'phase_id'                   => $request->phase_id,
                'passbook_no'                => $request->passbook_no,
                'customer_name'              => $request->customer_name,
                'customer_identification_no' => $request->customer_identification_no,
                
                // 🔥 Naye Customer Fields Map kiye gaye hain
                'father_name'                => $request->father_name,
                'spouse_name'                => $request->spouse_name,
                'customer_mobile'            => $request->customer_mobile,
                'address'                    => $request->address,

                'payment_mode'               => $request->payment_mode,
                'cheque_no'                  => $request->cheque_no,
                'bank_name'                  => $request->bank_name,
                'date_of_cheque'             => $request->date_of_cheque,
                'utr_no'                     => $request->utr_no,
                'transaction_no'             => $request->transaction_no,
                'transaction_date'           => $request->transaction_date,
                'received_bank_name'         => $request->received_bank_name,
                'remarks'                    => $request->remarks,
                'amount_details'             => $amountDetails,
                'property_type'              => $request->property_type,
                'unit_no'                    => $request->unit_no,
                'area_sqft'                  => $request->area_sqft,
                'total_amount'               => (float) $request->total_amount,
                'amount_received'            => $calculatedReceivedAmount,
                'balance_amount'             => (float) $request->total_amount - $calculatedReceivedAmount,
                'approved_by_emp_id'         => $request->approved_by_emp_id,
                'auth_ceo_id'                => $request->auth_ceo_id,

                // 🔥 Naye Received By Fields Map kiye gaye hain
                'received_by_emp_code'       => $request->received_by_emp_code,
               
                'received_by_department'     => $request->received_by_department,
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Receipt Saved Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $receipt = TempReceipt::with(['company', 'branch', 'approvedByEmployee.designation', 'authorizedCeo'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $receipt]);
    }
}