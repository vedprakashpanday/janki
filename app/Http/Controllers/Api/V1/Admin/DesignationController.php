<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    // GET: Saari designations fetch karein
    public function index()
    {
        $designations = Designation::latest()->get();
        return response()->json(['status' => 'success', 'data' => $designations]);
    }

    // POST: Nayi designation add karein
    public function store(Request $request)
    {
        $request->validate([
            'designation_name' => 'required|unique:designations,designation_name',
        ]);

        // Auto Generate Designation Code (e.g., DESIG-001)
        $count = Designation::count() + 1;
        $code = 'DESIG-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $designation = Designation::create([
            'designation_code' => $code,
            'designation_name' => $request->designation_name,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'data' => $designation]);
    }

    // GET: Ek specific designation fetch karein (Edit Modal ke liye)
    public function show($id)
    {
        $designation = Designation::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $designation]);
    }

    // PUT: Designation update karein
    public function update(Request $request, $id)
    {
        $request->validate([
            // ID ko ignore karein taaki same name par error na aaye
            'designation_name' => 'required|unique:designations,designation_name,' . $id, 
        ]);

        $designation = Designation::findOrFail($id);
        $designation->update([
            'designation_name' => $request->designation_name,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Designation Updated']);
    }

    // DELETE: Designation delete karein
    public function destroy($id)
    {
        Designation::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Designation Deleted']);
    }
}