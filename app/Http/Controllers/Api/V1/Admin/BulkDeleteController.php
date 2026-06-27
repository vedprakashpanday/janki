<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkDeleteController extends Controller
{
    public function delete(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        $table = $request->table_name;
        $ids = $request->ids;

        // 🔥 SECURITY: Sirf inhi tables se delete allow karein
        $allowedTables = [
            'adm_regist',       // Employees Table
            'designations',     // Designations Table
            'companies',        // Companies Table
            'branches',         // Branches Table
            'departments',
            'tasks',
            'auto_task_settings',
            'leave_applications'
        ];

        if (!in_array($table, $allowedTables)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized table operation!'], 403);
        }

        // ====================================================================
        // 🔥 🛡️ NEW OWNERSHIP & SECURITY CHECK LOGIC 🔥
        // ====================================================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!in_array($user->email, $developerEmails)) {
            
            // 1. CEO, Director ya HR galti se 'companies' table ko delete na kar dein.
            // Company udane ka power sirf Developer (God Mode) ke paas rahega.
            if ($table === 'companies') {
                return response()->json(['status' => 'error', 'message' => 'Strict Restriction: Only Developers can bulk delete Companies.'], 403);
            }

            // 2. Baki tables (adm_regist, branches, designations) ke liye Ownership Check:
            // Hum check karenge ki user jo IDs delete kar raha hai, kya wo usi ki company se judi hain?
            $invalidCount = DB::table($table)
                ->whereIn('id', $ids)
                ->where('company_id', '!=', $user->company_id) // Dusri company ka data check
                ->count();

            if ($invalidCount > 0) {
                return response()->json(['status' => 'error', 'message' => 'Scope Violation: You are trying to delete records belonging to another company!'], 403);
            }
        }
        // ====================================================================

        DB::beginTransaction();
        try {

            // 🔥 ORPHAN DATA CLEANUP LOGIC 🔥
            // Agar table 'adm_regist' hai, toh pehle unke 'member_id' fetch karo
            // taaki delete hone se pehle hum unki bank details bhi clean kar sakein.
            if ($table === 'adm_regist') {
                $memberIds = DB::table($table)->whereIn('id', $ids)->pluck('member_id')->toArray();

                // Agar member_ids mile hain, toh bank details table se unhe uda do
                if (!empty($memberIds)) {
                    DB::table('tbl_bank_details')->whereIn('member_id', $memberIds)->delete();
                }
            }

            // 🔥 NAYA: Tasks ke attachments aur logs delete karne ka logic
            if ($table === 'tasks') {
                DB::table('task_attachments')->whereIn('task_id', $ids)->delete();
                DB::table('task_progress_logs')->whereIn('task_id', $ids)->delete();
            }

            // Future mein agar 'members', 'agents' ya 'vendors' ki bhi bank details 
            // isi same table ('tbl_bank_details') mein aati hain, toh aap yahan un tables
            // ka naam add karke same logic chala sakte hain.

            // ---------------------------------------------------------
            // Main records delete karna (Sabhi allowed tables ke liye)
            // ---------------------------------------------------------
            DB::table($table)->whereIn('id', $ids)->delete();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => count($ids) . ' record(s) deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to delete records: ' . $e->getMessage()], 500);
        }
    }
}
