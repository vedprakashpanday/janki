<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        
        $query = Stock::with(['company', 'branch'])->latest();

        // 🛡️ ZERO-TRUST SCOPING
        if (!$context->is_god && !$context->is_director) {
            $query->where('company_id', $context->company_id);
        }

        // 1. DYNAMIC SEARCH
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'LIKE', "%{$search}%")
                  ->orWhere('serial_number', 'LIKE', "%{$search}%")
                  ->orWhere('category', 'LIKE', "%{$search}%");
            });
        }

        // 2. FILTERS
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $totalData = Stock::count();
        $totalFiltered = $query->count();

        // 3. EXPORT EXCEL LOGIC (Bina Pagination ke pura data bhejna)
        if ($request->has('export') && $request->export == 'true') {
            return response()->json([
                'status' => 'success',
                'data' => $query->get()
            ]);
        }
        if ($request->filled('entry_date')) {
            $query->whereDate('entry_date', $request->entry_date);
        }

        // 4. PAGINATION FOR DATATABLES / CARDS (Kewal 10-10 aayega)
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $stocks = $query->get();

        // 5. PERMISSIONS PAYLOAD FOR FRONTEND UI
        $permissions = [
            'can_add_direct'  => $context->is_god || in_array('stock_add_direct', $context->permissions),
            'can_add_request' => $context->is_god || in_array('stock_add_request', $context->permissions),
            'can_edit'        => $context->is_god || in_array('stock_edit', $context->permissions),
            'can_delete'      => $context->is_god || in_array('stock_delete', $context->permissions),
            'can_export'      => $context->is_god || in_array('stock_export', $context->permissions),
            'can_print'       => $context->is_god || in_array('stock_print', $context->permissions),
        ];

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data"            => $stocks,
            "permissions"     => $permissions
        ]);
    }

    public function store(Request $request)
    {
        $context = $this->getGlobalContext();

        $request->validate([
            'item_name' => 'required|string|max:255',
            'entry_date' => 'required|date',
            'price' => 'nullable|numeric',
            'total_quantity' => 'required|integer|min:1',
        ]);

        // 🔥 RBAC ADD LOGIC 🔥
        $hasDirect = $context->is_god || in_array('stock_add_direct', $context->permissions);
        $hasRequest = in_array('stock_add_request', $context->permissions);

        if (!$hasDirect && !$hasRequest && !$context->is_god) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! You cannot add stock.'], 403);
        }

        $finalStatus = $hasDirect ? 'active' : 'pending';
        $finalCompanyId = $request->company_id ?: $context->company_id;

        DB::beginTransaction();
        try {
            Stock::create([
                'company_id'       => $finalCompanyId,
                'branch_id'        => $request->branch_id ?: null, // Blank aaya to Head Office (Null)
                'item_name'        => $request->item_name,
                'category'         => $request->category,
                'entry_date'       => $request->entry_date,
                'serial_number'    => $request->serial_number,
                'price'            => $request->price ?? 0,
                'remarks' => $request->remarks,
                'total_quantity'   => $request->total_quantity,
                'lost_quantity'    => 0, // Default 0
                'status'           => $finalStatus
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => $finalStatus === 'active' ? 'Stock Added Successfully!' : 'Stock Request Sent for Approval!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $stock = Stock::findOrFail($id);

        if (!$context->is_god && !in_array('stock_edit', $context->permissions)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Edit Scope!'], 403);
        }

        DB::beginTransaction();
        try {
            $stock->update([
                'item_name'      => $request->item_name,
                'category'       => $request->category,
                'serial_number'  => $request->serial_number,
                'price'          => $request->price,
                'total_quantity' => $request->total_quantity,
                'remarks' => $request->remarks,
                'lost_quantity'  => $request->lost_quantity ?? $stock->lost_quantity, // Update if lost
                'branch_id'      => $request->branch_id ?: null
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Stock Updated Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god && !in_array('stock_delete', $context->permissions)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to delete!'], 403);
        }

        Stock::destroy($id);
        return response()->json(['status' => 'success', 'message' => 'Stock Deleted!']);
    }

    public function bulkDelete(Request $request)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god && !in_array('stock_delete', $context->permissions)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $ids = $request->ids;
        if (empty($ids)) return response()->json(['status' => 'error', 'message' => 'No items selected!'], 400);

        Stock::whereIn('id', $ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Selected stocks deleted successfully!']);
    }

    // =========================================================
    // 🔥 DEPENDENCY DROPDOWNS (CASCADING) 🔥
    // =========================================================

    public function searchCompanies(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Company::where('status', 'active');

        if (!$context->is_god && !$context->is_director) {
            $query->where('id', $context->company_id);
        }

        if ($request->has('q')) {
            $query->where('company_name', 'LIKE', '%' . $request->q . '%');
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->limit(20)->get(['id', 'company_name'])
        ]);
    }

    public function searchBranches(Request $request)
    {
        $companyId = $request->company_id;
        if (empty($companyId)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $company = Company::find($companyId);
        $companyName = $company ? $company->company_name : 'Selected Company';

        $branches = Branch::where('company_id', $companyId)->where('branch_status', 'active')->get(['id', 'branch_name']);
        
        // 🔥 NAYA LOGIC: Default "Head Office" option at the top 🔥
        $result = [];
        $result[] = [
            'id' => '', 
            'branch_name' => "Head Office ({$companyName})"
        ];

        foreach ($branches as $branch) {
            $result[] = [
                'id' => $branch->id,
                'branch_name' => $branch->branch_name
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    // Future use for Print Component (returns pure HTML view via API)
    public function printPreview(Request $request)
    {
        // Yahan filters collect karke direct data bhejenge blade file mein
        // Future me jab print blade banega toh yahan logic daalenge
        return "Print Preview Work in Progress";
    }


    public function getFilterOptions(Request $request)
    {
        $query = Stock::where('status', 'active');
        
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // 1. Get all unique categories
        $categories = (clone $query)->whereNotNull('category')->distinct()->pluck('category');

        // 2. Filter products if a specific category is requested from frontend
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        $products = (clone $query)->whereNotNull('item_name')->distinct()->pluck('item_name');

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
            'products' => $products
        ]);
    }

    public function show($id)
    {
        $stock = Stock::with(['company', 'branch'])->find($id);
        if (!$stock) {
            return response()->json(['status' => 'error', 'message' => 'Stock not found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $stock]);
    }

    // Generate Report (Print View)
   // Generate Report (Print View)
    public function printReport(Request $request)
    {
        // 🔥 FIX: Naye tab me auth headers nahi aate, isliye URL wale token se login kara rahe hain
        if (!auth()->check() && $request->has('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token) {
                auth()->login($token->tokenable);
            }
        }

        $context = $this->getGlobalContext();
        
        $query = Stock::with(['company', 'branch']);
        
        // 🔥 FIX: Safe context check (Agar by chance context null ho to crash na kare)
        if ($context && !$context->is_god && !$context->is_director) {
            $query->where('company_id', $context->company_id);
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('product')) $query->where('item_name', $request->product);

        $stocks = $query->get();
        
        // Print header components ke liye company aur branch data fetch karna
        $company = $request->filled('company_id') ? Company::find($request->company_id) : null;
        $branch = $request->filled('branch_id') ? Branch::find($request->branch_id) : null;

        if(!$company && $stocks->isNotEmpty()) {
            $company = $stocks->first()->company;
        }

        return view('admin.stocks.print_report', compact('stocks', 'company', 'branch'));
    }


}