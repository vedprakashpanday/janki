<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IncentiveType;

class IncentiveTypeApiController extends Controller
{
    // 1. Get all active incentive types for the dropdown
    public function getActive(Request $request)
    {
        $types = IncentiveType::where('status', 'active')->orderBy('name', 'asc')->get(['id', 'name']);
        
        return response()->json([
            'status' => 'success',
            'data' => $types
        ]);
    }

    // 2. Store a new incentive type from the nested modal
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:incentive_types,name'
        ]);

        try {
            $type = IncentiveType::create([
                'name' => trim($request->name),
                'status' => 'active'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Incentive Type Added Successfully!',
                'data' => $type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save Incentive Type.'
            ], 500);
        }
    }
}