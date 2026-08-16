<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PropertyType;
use App\Models\PropertyCategory;
use App\Models\PropertyArea;

class PropertyDependencyApiController extends Controller
{
    // Fetch Types based on Phase ID
    public function getTypes($phase_id)
    {
        $types = PropertyType::where('phase_id', $phase_id)
            ->where('status', 'active')
            ->select('id', 'type_name')
            ->get();
        return response()->json(['success' => true, 'data' => $types]);
    }

    // Fetch Categories based on Type ID
    public function getCategories($type_id)
    {
        $categories = PropertyCategory::where('property_type_id', $type_id)
            ->where('status', 'active')
            ->select('id', 'category_name')
            ->get();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    // Fetch Areas based on Category ID
    public function getAreas($category_id)
    {
        $areas = PropertyArea::where('property_category_id', $category_id)
            ->where('status', 'active')
            ->select('id', 'area_name', 'measurement_unit')
            ->get();
        return response()->json(['success' => true, 'data' => $areas]);
    }
}