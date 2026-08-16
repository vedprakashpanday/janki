<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCategory;
use App\Models\StockType;
use App\Models\StockBrand;
use Illuminate\Support\Facades\DB;
use App\Models\StockAttribute;

class StockMasterController extends Controller
{
    private function getPermissions() {
        $context = $this->getGlobalContext();
        return [
            'can_add_direct'  => $context->is_god || in_array('stock_master_add_direct', $context->permissions),
            'can_add_request' => $context->is_god || in_array('stock_master_add_request', $context->permissions),
            'can_edit'        => $context->is_god || in_array('stock_master_edit', $context->permissions),
            'can_delete'      => $context->is_god || in_array('stock_master_delete', $context->permissions),
            'can_appr'        => $context->is_god || in_array('stock_master_appr', $context->permissions),
            'can_rej'         => $context->is_god || in_array('stock_master_rej', $context->permissions),
        ];
    }

    private function determineStatus($perms) {
        if ($perms['can_add_direct']) return 'active';
        if ($perms['can_add_request']) return 'pending';
        return false;
    }

    // ==========================================
    // 🟢 CATEGORY LOGIC
    // ==========================================
    public function indexCategories(Request $request) {
        $query = StockCategory::latest();
        if ($request->has('search') && $request->input('search.value')) {
            $query->where('name', 'LIKE', "%{$request->input('search.value')}%");
        }
        $totalData = StockCategory::count();
        $totalFiltered = $query->count();
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }
        return response()->json([
            "draw" => intval($request->input('draw')), "recordsTotal" => $totalData, "recordsFiltered" => $totalFiltered,
            "data" => $query->get(), "permissions" => $this->getPermissions()
        ]);
    }

    public function storeCategory(Request $request) {
        $perms = $this->getPermissions();
        $status = $this->determineStatus($perms);
        if (!$status) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        StockCategory::create(['name' => $request->name, 'status' => $status]);
        return response()->json(['status' => 'success', 'message' => $status == 'active' ? 'Category Saved!' : 'Request Sent!']);
    }

    public function updateCategory(Request $request, $id) {
        $perms = $this->getPermissions();
        if (!$perms['can_edit']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        StockCategory::findOrFail($id)->update(['name' => $request->name]);
        return response()->json(['status' => 'success', 'message' => 'Category Updated!']);
    }

    public function bulkDeleteCategories(Request $request) {
        $perms = $this->getPermissions();
        if (!$perms['can_delete']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        StockCategory::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Categories Deleted!']);
    }

    public function statusCategory(Request $request, $id) {
        $perms = $this->getPermissions();
        $action = $request->action; // 'approve' or 'reject'
        $status = ($action === 'approve') ? 'active' : 'inactive';
        
        if ($action === 'approve' && !$perms['can_appr']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        if ($action === 'reject' && !$perms['can_rej']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        StockCategory::findOrFail($id)->update(['status' => $status]);
        return response()->json(['status' => 'success', 'message' => 'Status Updated to ' . strtoupper($status)]);
    }

    // ==========================================
    // 🟢 TYPE LOGIC
    // ==========================================
    public function indexTypes(Request $request) {
        $query = StockType::with('category')->latest();
        if ($request->filled('category_id')) $query->where('category_id', $request->category_id);
        if ($request->has('search') && $request->input('search.value')) {
            $query->where('name', 'LIKE', "%{$request->input('search.value')}%");
        }
        $totalData = StockType::count();
        $totalFiltered = $query->count();
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }
        return response()->json([
            "draw" => intval($request->input('draw')), "recordsTotal" => $totalData, "recordsFiltered" => $totalFiltered,
            "data" => $query->get(), "permissions" => $this->getPermissions()
        ]);
    }

    public function storeType(Request $request) {
        $perms = $this->getPermissions();
        $status = $this->determineStatus($perms);
        if (!$status) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        StockType::create(['category_id' => $request->category_id, 'name' => $request->name, 'status' => $status]);
        return response()->json(['status' => 'success', 'message' => $status == 'active' ? 'Type Saved!' : 'Request Sent!']);
    }

    public function updateType(Request $request, $id) {
        $perms = $this->getPermissions();
        if (!$perms['can_edit']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        StockType::findOrFail($id)->update(['name' => $request->name, 'category_id' => $request->category_id]);
        return response()->json(['status' => 'success', 'message' => 'Type Updated!']);
    }

    public function bulkDeleteTypes(Request $request) {
        $perms = $this->getPermissions();
        if (!$perms['can_delete']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        StockType::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Types Deleted!']);
    }

    public function statusType(Request $request, $id) {
        $perms = $this->getPermissions();
        $action = $request->action; 
        $status = ($action === 'approve') ? 'active' : 'inactive';
        
        if ($action === 'approve' && !$perms['can_appr']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        if ($action === 'reject' && !$perms['can_rej']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        StockType::findOrFail($id)->update(['status' => $status]);
        return response()->json(['status' => 'success', 'message' => 'Status Updated!']);
    }

    // ==========================================
    // 🟢 BRAND LOGIC
    // ==========================================
    public function indexBrands(Request $request) {
        $query = StockBrand::latest();
        if ($request->has('search') && $request->input('search.value')) {
            $query->where('name', 'LIKE', "%{$request->input('search.value')}%");
        }
        $totalData = StockBrand::count();
        $totalFiltered = $query->count();
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }
        return response()->json([
            "draw" => intval($request->input('draw')), "recordsTotal" => $totalData, "recordsFiltered" => $totalFiltered,
            "data" => $query->get(), "permissions" => $this->getPermissions()
        ]);
    }

    public function storeBrand(Request $request) {
        $perms = $this->getPermissions();
        $status = $this->determineStatus($perms);
        if (!$status) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        StockBrand::create(['name' => $request->name, 'status' => $status]);
        return response()->json(['status' => 'success', 'message' => $status == 'active' ? 'Brand Saved!' : 'Request Sent!']);
    }

    public function updateBrand(Request $request, $id) {
        $perms = $this->getPermissions();
        if (!$perms['can_edit']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        StockBrand::findOrFail($id)->update(['name' => $request->name]);
        return response()->json(['status' => 'success', 'message' => 'Brand Updated!']);
    }

    public function bulkDeleteBrands(Request $request) {
        $perms = $this->getPermissions();
        if (!$perms['can_delete']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        StockBrand::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Brands Deleted!']);
    }

    public function statusBrand(Request $request, $id) {
        $perms = $this->getPermissions();
        $action = $request->action; 
        $status = ($action === 'approve') ? 'active' : 'inactive';
        
        if ($action === 'approve' && !$perms['can_appr']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        if ($action === 'reject' && !$perms['can_rej']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        StockBrand::findOrFail($id)->update(['status' => $status]);
        return response()->json(['status' => 'success', 'message' => 'Status Updated!']);
    }

    // Public Dropdown API
    public function getDropdownCategories() {
        return response()->json(StockCategory::where('status', 'active')->get(['id', 'name']));
    }


    // ==========================================
    // 🟢 SPECIFICATIONS (ATTRIBUTES) LOGIC
    // ==========================================
    public function indexAttributes(Request $request) {
        $query = StockAttribute::with(['categories', 'options'])->latest();
        
        // Filter by specific category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('stock_categories.id', $request->category_id);
            });
        }

        if ($request->has('search') && $request->input('search.value')) {
            $query->where('name', 'LIKE', "%{$request->input('search.value')}%");
        }
        
        $totalData = StockAttribute::count();
        $totalFiltered = $query->count();
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }
        return response()->json([
            "draw" => intval($request->input('draw')), "recordsTotal" => $totalData, "recordsFiltered" => $totalFiltered,
            "data" => $query->get(), "permissions" => $this->getPermissions()
        ]);
    }

    public function storeAttribute(Request $request) {
        $perms = $this->getPermissions();
        $status = $this->determineStatus($perms);
        if (!$status) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        DB::beginTransaction();
        try {
            $attribute = StockAttribute::create(['name' => $request->name, 'status' => $status]);
            
            // Map to Category
            if($request->category_id) {
                $attribute->categories()->attach($request->category_id);
            }
            
            // Save Options (comma separated values)
            $options = array_filter(array_map('trim', explode(',', $request->options)));
            foreach($options as $opt) {
                $attribute->options()->create(['value' => $opt]);
            }
            
            DB::commit();
            return response()->json(['status' => 'success', 'message' => $status == 'active' ? 'Specification Saved!' : 'Request Sent!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateAttribute(Request $request, $id) {
        $perms = $this->getPermissions();
        if (!$perms['can_edit']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        
        DB::beginTransaction();
        try {
            $attribute = StockAttribute::findOrFail($id);
            $attribute->update(['name' => $request->name]);
            
            if($request->category_id) {
                $attribute->categories()->sync([$request->category_id]);
            }
            
            // Refresh Options
            $attribute->options()->delete();
            $options = array_filter(array_map('trim', explode(',', $request->options)));
            foreach($options as $opt) {
                $attribute->options()->create(['value' => $opt]);
            }
            
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Specification Updated!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkDeleteAttributes(Request $request) {
        $perms = $this->getPermissions();
        if (!$perms['can_delete']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        StockAttribute::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Specifications Deleted!']);
    }

    public function statusAttribute(Request $request, $id) {
        $perms = $this->getPermissions();
        $action = $request->action; 
        $status = ($action === 'approve') ? 'active' : 'inactive';
        
        if ($action === 'approve' && !$perms['can_appr']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        if ($action === 'reject' && !$perms['can_rej']) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        StockAttribute::findOrFail($id)->update(['status' => $status]);
        return response()->json(['status' => 'success', 'message' => 'Status Updated!']);
    }


}