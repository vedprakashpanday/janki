<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use App\Models\StockCategory;
use App\Models\StockType;

class StockEntryApiController extends Controller
{
   private function getPermissions() {
        $context = $this->getGlobalContext();
        $isAdmin = $context->is_god || (isset($context->is_admin) && $context->is_admin);

        return [
            'can_add_direct'  => $isAdmin || (is_array($context->permissions) && in_array('stock_daily_add_direct', $context->permissions)),
            'can_add_request' => $isAdmin || (is_array($context->permissions) && in_array('stock_daily_add_request', $context->permissions)),
            'can_edit'        => $isAdmin || (is_array($context->permissions) && in_array('stock_daily_edit', $context->permissions)),
            'can_delete'      => $isAdmin || (is_array($context->permissions) && in_array('stock_daily_delete', $context->permissions)),
            'can_appr'        => $isAdmin || (is_array($context->permissions) && in_array('stock_daily_appr', $context->permissions)),
            'can_rej'         => $isAdmin || (is_array($context->permissions) && in_array('stock_daily_rej', $context->permissions)),
        ];
    }

    // 1. Get Active Employees for Incharge Dropdown
    public function getActiveEmployees()
    {
        $employees = DB::table('adm_regist')
            ->where('emp_status', 'active')
            ->select('id', 'full_name', 'member_id')
            ->get();

        return response()->json(['status' => 'success', 'data' => $employees]);
    }

    // 2. Get Types & Specifications dynamically
    public function getCategoryDependencies($categoryId)
    {
        $types = StockType::where('category_id', $categoryId)->where('status', 'active')->get(['id', 'name']);
        
        $category = StockCategory::with(['attributes' => function($q) {
            $q->where('status', 'active')->with('options');
        }])->find($categoryId);

        return response()->json([
            'status' => 'success',
            'types' => $types,
            'attributes' => $category ? $category->attributes : []
        ]);
    }

    // 3. Store the Stock Entry (with RBAC)
    public function storeStockEntry(Request $request)
    {
        $perms = $this->getPermissions();

        if (!$perms['can_add_direct'] && !$perms['can_add_request']) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! You cannot add stock.'], 403);
        }

        $finalStatus = $perms['can_add_direct'] ? 'active' : 'pending';

        $request->validate([
            'company_id' => 'required',
            'category_id' => 'required',
            'incharge_id' => 'required',
            'item_name' => 'required|string|max:255',
            'total_quantity' => 'required|numeric|min:1',
            'purchase_date' => 'required|date',
            'price' => 'nullable|numeric'
        ]);

        DB::beginTransaction();
        try {
            $stock = Stock::create([
                'company_id' => $request->company_id,
                'branch_id' => $request->branch_id ?: null,
                'category_id' => $request->category_id,
                'type_id' => $request->type_id ?: null,
                'brand_id' => $request->brand_id ?: null,
                'incharge_id' => $request->incharge_id,
                'item_name' => $request->item_name, 
                'purchase_date' => $request->purchase_date,
                'entry_date' => date('Y-m-d'), 
                'serial_number' => $request->serial_number,
                'price' => $request->price ?? 0,
                'total_quantity' => $request->total_quantity,
                'lost_quantity' => 0,
                'remarks' => $request->remarks,
                'status' => $finalStatus
            ]);

            if ($request->has('attributes') && is_array($request->attributes)) {
                $attrValues = [];
                foreach ($request->attributes as $attrId => $optionId) {
                    if (!empty($optionId)) {
                        $attrValues[] = [
                            'stock_id' => $stock->id,
                            'attribute_id' => $attrId,
                            'attribute_option_id' => $optionId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                if (count($attrValues) > 0) {
                    DB::table('stock_attribute_values')->insert($attrValues);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success', 
                'message' => $finalStatus === 'active' ? 'Stock Entry Saved Directly!' : 'Stock Entry Request Sent!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    // 4. Get Today's Entries for DataTable
    public function getTodayEntries(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Stock::with(['category', 'type'])->whereDate('entry_date', date('Y-m-d'))->latest();

        // Zero Trust Scoping
        if (!$context->is_god && !$context->is_director) {
            $query->where('company_id', $context->company_id);
        }

        $totalData = $query->count();
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalData,
            "data" => $query->get(),
            "permissions" => $this->getPermissions()
        ]);
    }

    // 5. Approve / Reject Action
    public function updateStatus(Request $request, $id)
    {
        $perms = $this->getPermissions();
        $action = $request->action; 
        
        if ($action === 'approve' && !$perms['can_appr']) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to Approve!'], 403);
        }
        if ($action === 'reject' && !$perms['can_rej']) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to Reject!'], 403);
        }

        $status = ($action === 'approve') ? 'active' : 'inactive';
        Stock::findOrFail($id)->update(['status' => $status]);
        
        return response()->json([
            'status' => 'success', 
            'message' => 'Entry marked as ' . strtoupper($status)
        ]);
    }

    // 6. Bulk Delete Entries
    public function bulkDelete(Request $request)
    {
        $context = $this->getGlobalContext();
        $isAdmin = $context->is_god || (isset($context->is_admin) && $context->is_admin);
        
        if (!$isAdmin && (!is_array($context->permissions) || !in_array('stock_daily_delete', $context->permissions))) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $ids = $request->ids;
        if (empty($ids)) return response()->json(['status' => 'error', 'message' => 'No items selected!'], 400);

        Stock::whereIn('id', $ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Selected entries deleted successfully!']);
    }


}