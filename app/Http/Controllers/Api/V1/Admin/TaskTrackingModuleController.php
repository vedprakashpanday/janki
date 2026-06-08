<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\TaskTrackingModule;

class TaskTrackingModuleController extends Controller
{
    /**
     * 🔥 Developer Scope Check
     */
    private function isDeveloper($user)
    {
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        return in_array($user->email, $developerEmails);
    }

    /**
     * 1. GET ALL TABLES (For Dropdown)
     */
    public function getTables()
    {
        if (!$this->isDeveloper(auth()->user())) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! Developer access required.'], 403);
        }

        // MySQL ke saare tables fetch karne ka safe tareeka
        $tables = array_map('current', DB::select('SHOW TABLES'));

        // Kuch sensitive Laravel internal tables ko hide kar sakte hain
        $ignoreTables = ['migrations', 'personal_access_tokens', 'failed_jobs', 'password_reset_tokens'];
        $filteredTables = array_values(array_diff($tables, $ignoreTables));

        return response()->json(['status' => 'success', 'data' => $filteredTables]);
    }

    /**
     * 2. GET COLUMNS FOR SELECTED TABLE
     */
    public function getColumns(Request $request)
    {
        if (!$this->isDeveloper(auth()->user())) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! Developer access required.'], 403);
        }

        $request->validate(['table_name' => 'required|string']);

        if (!Schema::hasTable($request->table_name)) {
            return response()->json(['status' => 'error', 'message' => 'Table not found in database.'], 404);
        }

        // Table ke columns dynamically fetch karna
        $columns = Schema::getColumnListing($request->table_name);

        return response()->json(['status' => 'success', 'data' => $columns]);
    }

    /**
     * 3. STORE TRACKING CONFIGURATION
     */
    public function store(Request $request)
    {
        if (!$this->isDeveloper(auth()->user())) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! Developer access required.'], 403);
        }

        $request->validate([
            'task_category_name' => 'required|string|unique:task_tracking_modules,task_category_name',
            'target_table' => 'required|string',
            'user_id_column' => 'required|string',
            'join_column' => 'required|string',
            'date_column' => 'required|string',
        ]);

        $module = TaskTrackingModule::create([
            'task_category_name' => $request->task_category_name,
            'target_table' => $request->target_table,
            'user_id_column' => $request->user_id_column,
            'join_column' => $request->join_column,
            'date_column' => $request->date_column,
            'is_dynamic' => true
        ]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Dynamic Tracking Module Master Configured Successfully!', 
            'data' => $module
        ]);
    }

    /**
     * 4. LIST ALL CONFIGURED MODULES (For Task Creation Dropdown)
     */
    public function index()
    {
        // Isko task assigner (Admin/Director/Emp) access kar sakta hai
        $modules = TaskTrackingModule::all();
        return response()->json(['status' => 'success', 'data' => $modules]);
    }
}