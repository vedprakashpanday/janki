<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Phase;
use App\Services\MediaConverterService; // Aapki image compression service

class PhaseApiController extends Controller
{
    // 1. Form Dropdown Data ke liye
    public function create()
    {
        $context = $this->getGlobalContext();
        
        $response = [
            'is_director' => $context->is_director,
            'is_god' => $context->is_god,
            'locked_company_id' => null,
            'companies' => [],
            'branches' => []
        ];

        if ($context->is_god) {
            $response['companies'] = \App\Models\Company::where('status', 'active')->get();
        } elseif ($context->is_director) {
            $response['locked_company_id'] = $context->company_id;
            $response['branches'] = \App\Models\Branch::where('company_id', $context->company_id)->get();
        }

        return response()->json(['success' => true, 'data' => $response]);
    }

    // 2. Dependent Dropdown (Branches laane ke liye)
    public function getBranches($company_id)
    {
        $branches = \App\Models\Branch::where('company_id', $company_id)
            ->select('id', 'branch_name')
            ->get();
            
        return response()->json(['success' => true, 'data' => $branches]);
    }

    // 3. Phase List Data (Table/Cards ke liye)
    public function index()
    {
        $context = $this->getGlobalContext();
        $query = Phase::with(['company', 'branch']);

        if ($context->is_god) {
            // God sees all
        } elseif ($context->is_director) {
            $query->where('company_id', $context->company_id);
        } else {
            $query->where('company_id', $context->company_id)
                  ->where('branch_id', $context->branch_id);
        }

        $phases = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $phases]);
    }

    // 4. 🔥 NAYA METHOD: Phase Data Save Karne ke liye 🔥
    public function store(Request $request, MediaConverterService $mediaService)
    {
        $context = $this->getGlobalContext();

        // Validation Rules
        $rules = [
            'phase_name' => 'required|string|max:255',
            'phase_location' => 'required|string|max:255',
            'phase_details' => 'required|string',
            'phase_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,bmp|max:5120', 
            'khatiyan_map' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120', // Max 5MB
            'phase_google_map_url' => 'nullable|url'
        ];

        // Role ke hisaab se validation
        if ($context->is_god) {
            $rules['company_id'] = 'required|integer';
            $rules['branch_id'] = 'nullable|integer'; // Head Office (null) ke liye nullable
        } elseif ($context->is_director) {
            $rules['branch_id'] = 'nullable|integer';
        }

        $request->validate($rules);

        $data = $request->only([
            'phase_name', 'phase_location', 'phase_details', 'phase_google_map_url'
        ]);

        // Security Override: Dropdown hacking rokne ke liye backend se IDs set karna
        if ($context->is_god) {
            $data['company_id'] = $request->company_id;
            $data['branch_id'] = $request->branch_id;
        } elseif ($context->is_director) {
            $data['company_id'] = $context->company_id; // Locked
            $data['branch_id'] = $request->branch_id;   
        } else {
            $data['company_id'] = $context->company_id; // Locked
            $data['branch_id'] = $context->branch_id;   // Locked
        }

        $data['created_by'] = $context->profile_id;

        // Image compression and saving
        if ($request->hasFile('phase_image')) {
            $media = $mediaService->uploadAndConvert($request->file('phase_image'));
            if ($media) {
                $data['phase_image'] = $media->file_path; 
            }
        }

        if ($request->hasFile('khatiyan_map')) {
            $media = $mediaService->uploadAndConvert($request->file('khatiyan_map'));
            if ($media) {
                $data['khatiyan_map'] = $media->file_path; 
            }
        }

        // Database me save
        Phase::create($data);

        return response()->json([
            'success' => true, 
            'message' => 'Phase created successfully.'
        ]);
    }

    // 5. Bulk Delete
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        Phase::whereIn('id', $request->ids)->delete();

        return response()->json(['success' => true, 'message' => 'Phases deleted successfully']);
    }

    // 6. NAYA METHOD: Ek specific Phase ka data laane ke liye (Edit Form ke liye)
    public function show($id)
    {
        $context = $this->getGlobalContext();
        $phase = Phase::with(['company', 'branch'])->findOrFail($id);

        // Security Scope Check
        if (!$context->is_god) {
            if ($context->is_director && $phase->company_id != $context->company_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);
            } elseif (!$context->is_director && ($phase->company_id != $context->company_id || $phase->branch_id != $context->branch_id)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json(['success' => true, 'data' => $phase]);
    }

    // 7. NAYA METHOD: Edited Data ko Database me Update karne ke liye
    public function update(Request $request, $id, \App\Services\MediaConverterService $mediaService)
    {
        $context = $this->getGlobalContext();
        $phase = Phase::findOrFail($id);

        $rules = [
            'phase_name' => 'required|string|max:255',
            'phase_location' => 'required|string|max:255',
            'phase_details' => 'required|string',
            'phase_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,bmp|max:5120',
            'khatiyan_map' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
            'phase_google_map_url' => 'nullable|url'
        ];

        if ($context->is_god) {
            $rules['company_id'] = 'required|integer';
            $rules['branch_id'] = 'nullable|integer';
        } elseif ($context->is_director) {
            $rules['branch_id'] = 'nullable|integer';
        }

        $request->validate($rules);

       $data = $request->only(['phase_name', 'phase_location', 'phase_details', 'phase_google_map_url']);

       

        if ($context->is_god) {
            $data['company_id'] = $request->company_id;
            $data['branch_id'] = $request->branch_id;
        } elseif ($context->is_director) {
            $data['company_id'] = $context->company_id;
            $data['branch_id'] = $request->branch_id;
        }

        // Image Update Logic
        if ($request->hasFile('phase_image')) {
            $media = $mediaService->uploadAndConvert($request->file('phase_image'));
            if ($media) {
                // Agar purani image delete karni ho toh aap yahan File::delete(public_path($phase->phase_image)) laga sakte hain
                $data['phase_image'] = $media->file_path; 
            }
        }

        if ($request->hasFile('khatiyan_map')) {
            $media = $mediaService->uploadAndConvert($request->file('khatiyan_map'));
            if ($media) {
                $data['khatiyan_map'] = $media->file_path;
            }
        }

         // 🔥 NAYA FIX: Agar user ne image clear ki hai, to null set karo
        if ($request->has('remove_phase_image') && $request->remove_phase_image == '1') {
            $data['phase_image'] = null;
        }
        if ($request->has('remove_khatiyan_map') && $request->remove_khatiyan_map == '1') {
            $data['khatiyan_map'] = null;
        }

        $phase->update($data);

        return response()->json(['success' => true, 'message' => 'Phase updated successfully.']);
    }


    public function searchDynamicList(Request $request)
    {
        $search = $request->query('q');
        if (strlen($search) < 3) {
            return response()->json([]);
        }

        $phases = Phase::with('company:id,company_name')
            ->where('phase_name', 'LIKE', "%{$search}%")
            ->limit(10)
            ->get(['id', 'phase_name', 'company_id']);

        $result = $phases->map(function ($phase) {
            return [
                'id' => $phase->id,
                'name' => $phase->phase_name,
                'company_id' => $phase->company_id,
                'company_name' => $phase->company ? $phase->company->company_name : 'N/A'
            ];
        });

        return response()->json($result);
    }

    public function saveCanvasAsBaseMap(Request $request)
    {
        $request->validate([
            'phase_id' => 'required|integer',
            'image_base64' => 'required|string'
        ]);

        $phase = \App\Models\Phase::findOrFail($request->phase_id);

        // Base64 string ko decode karke image file banana
        $image_parts = explode(";base64,", $request->image_base64);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        
        $fileName = 'generated_layout_phase_' . $phase->id . '_' . time() . '.png';
        $filePath = public_path('uploads/maps/' . $fileName);
        
        // Ensure directory exists
        if (!file_exists(public_path('uploads/maps'))) {
            mkdir(public_path('uploads/maps'), 0777, true);
        }

        // Save file to public folder
        file_put_contents($filePath, $image_base64);

        // Update Phase table
        $dbPath = 'uploads/maps/' . $fileName;
        $phase->update(['khatiyan_map' => $dbPath]);

        return response()->json([
            'success' => true, 
            'message' => 'Layout saved as Base Map successfully!',
            'map_url' => asset($dbPath)
        ]);
    }

}