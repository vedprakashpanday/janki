<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteVisitSetting;
use Illuminate\Http\Request;

class SiteVisitSettingController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god) {
            $userPerms = self::getLiveActivePermissions(auth()->user());
            if (!in_array('sv_settings', $userPerms)) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }
        $settings = SiteVisitSetting::orderBy('start_date', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $settings]);
    }

    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god) {
            $userPerms = self::getLiveActivePermissions(auth()->user());
            if (!in_array('sv_settings', $userPerms)) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        $request->validate([
            'min_visits' => 'required|integer|min:0',
            'max_visits' => 'nullable|integer|gt:min_visits',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
        ]);

        $setting = SiteVisitSetting::create([
            'min_visits' => $request->min_visits,
            'max_visits' => $request->max_visits,
            'amount' => $request->amount,
            'start_date' => $request->start_date,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Site Visit Setting Saved!']);
    }

    public function destroy($id)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);

        SiteVisitSetting::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Setting Deleted!']);
    }

    // 🟢 NAYA: Bulk Delete API
    public function bulkDelete(Request $request)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god) return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);

        $request->validate(['ids' => 'required|array']);
        SiteVisitSetting::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Selected Settings Deleted!']);
    }

    public function show($id)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god) {
            $userPerms = self::getLiveActivePermissions(auth()->user());
            if (!in_array('sv_settings', $userPerms)) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }
        
        $setting = SiteVisitSetting::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $setting]);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god) {
            $userPerms = self::getLiveActivePermissions(auth()->user());
            if (!in_array('sv_settings', $userPerms)) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        $request->validate([
            'min_visits' => 'required|integer|min:0',
            'max_visits' => 'nullable|integer|gt:min_visits',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
        ]);

        $setting = SiteVisitSetting::findOrFail($id);
        $setting->update([
            'min_visits' => $request->min_visits,
            'max_visits' => $request->max_visits,
            'amount' => $request->amount,
            'start_date' => $request->start_date,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Site Visit Setting Updated!']);
    }


}