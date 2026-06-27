<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB; // 🔥 Pivot Data Update Ke Liye Zaroori
use App\Services\MediaConverterService;

class CompanyApiController extends Controller
{
   public function index(Request $request)
    {
        $query = Company::with(['parent', 'directors', 'ceos']);

        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        // 🔥 NAYA: GOD-MODE CHECK (Master Emails OR CEO Model)
        $isGodMode = false;
        if ($user && (in_array($user->email, $developerEmails) || class_basename($user) === 'SuperAdmin')) {
            $isGodMode = true;
        }

        // Agar user ke paas 'company_view' nahi hai aur wo God Mode me nahi hai, toh sirf apni company dikhao
        if (!$isGodMode && (!$user || !$user->can('company_view'))) {
            $query->where('id', $user->company_id ?? 0);
        }

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('company_name', 'LIKE', "%{$search}%")
                ->orWhere('company_code', 'LIKE', "%{$search}%")
                ->orWhere('state', 'LIKE', "%{$search}%");
        }

        $totalData = Company::count();
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $query->offset($start)->limit($length);
        }

        $companies = $query->orderBy('id', 'desc')->get();

        $data = $companies->map(function ($c) {
            $directorList = $c->directors->map(function($dir) {
                return $dir->full_name . ' <small class="text-muted">(' . $dir->pivot->role . ')</small>';
            })->toArray();

            $ceoList = $c->ceos->map(function($ceo) {
                return $ceo->full_name . ' <small class="text-muted">(' . $ceo->pivot->role . ')</small>';
            })->toArray();

            $allBoard = array_merge($directorList, $ceoList);
            $directorsHtml = implode('<br>', $allBoard);

            return [
                'id' => $c->id,
                'company_name' => $c->company_name,
                'company_code' => '<span class="badge bg-dark">' . $c->company_code . '</span>',
                'parent_name' => $c->parent ? $c->parent->company_name : '<span class="badge bg-secondary">Master Company</span>',
                'directors_html' => $directorsHtml ?: 'No Board Member',
                'state' => $c->state ?? '-',
                'district' => $c->district ?? '-',
                'status' => $c->status
            ];
        });

        // 🔥 NAYA: GOD MODE WILL OVERRIDE ALL PERMISSIONS
        $permissions = [
            'can_add_direct'  => $isGodMode ? true : ($user ? $user->can('company_add_direct') : false),
            'can_add_request' => $isGodMode ? true : ($user ? $user->can('company_add_request') : false),
            'can_edit'        => $isGodMode ? true : ($user ? $user->can('company_edit') : false),
            'can_delete'      => $isGodMode ? true : ($user ? $user->can('company_delete') : false),
            'can_print'       => $isGodMode ? true : ($user ? $user->can('company_print') : false),
            'can_export'      => $isGodMode ? true : ($user ? $user->can('company_export') : false), // Excel Permission
        ];

        return response()->json([
            "draw" => intval($request->input('draw', 0)), 
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $data,
            "permissions" => $permissions
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        $isGodMode = false;
        if ($user && (in_array($user->email, $developerEmails) || class_basename($user) === 'SuperAdmin')) {
            $isGodMode = true;
        }

        // 🛡️ PERMISSION CHECK 
        if (!$isGodMode && (!$user->can('company_add_direct') && !$user->can('company_add_request'))) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! You do not have permission to add or request a company.'], 403);
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_code' => 'required|string|max:10|unique:companies,company_code',
            'cin_no'       => 'required|string|max:255',
            'company_logo' => 'nullable|mimes:jpeg,png,jpg,gif,webp,bmp|max:5120'
        ]);

        $logoPath = null;
        if ($request->hasFile('company_logo')) {
            $converter = new \App\Services\MediaConverterService();
            $media = $converter->uploadAndConvert($request->file('company_logo'));
            if ($media) {
                $logoPath = $media->file_path;
            }
        }

        $finalStatus = $request->status ?? 'active';
        
        // Agar god mode nahi hai aur sirf request permission hai, to pending set karo
        if (!$isGodMode && !$user->can('company_add_direct') && $user->can('company_add_request')) {
            $finalStatus = 'pending';
        }
        // 🔥 NAYA: Extract Location
        $mapData = $this->extractLatLng($request->map_url);

        $company = Company::create([
            'company_name'  => $request->company_name,
            'company_code'  => strtoupper($request->company_code),
            'company_logo'  => $logoPath,
            'cin_no'        => strtoupper($request->cin_no),
            'iso_no'        => $request->iso_no,
            'trademark'     => $request->trademark,
            'logo_reg_no'   => $request->logo_reg_no,
            'parent_id'     => $request->parent_id ?: null,
            'phone'         => $request->phone,
            'email'         => $request->email,
            'state'         => $request->state,
            'district'      => $request->district,
            'address'       => $request->address,
            'gst_no'        => $request->gst_no,
            'status'        => $finalStatus,
            
            // 🔥 NAYA: Database me fields save karein
            'map_url'       => $request->map_url,
            'latitude'      => $mapData['latitude'],
            'longitude'     => $mapData['longitude']
        ]);

        if ($request->has('board_assignments')) {
            $boardData = json_decode($request->board_assignments, true);
            $insertData = [];
            foreach ($boardData as $board) {
                $insertData[] = [
                    'company_id'  => $company->id,
                    'director_id' => $board['director_id'] ?? null,
                    'ceo_id'      => $board['ceo_id'] ?? null,
                    'role'        => $board['role'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
            if (!empty($insertData)) {
                \Illuminate\Support\Facades\DB::table('company_director')->insert($insertData);
            }
        }

        $message = $finalStatus === 'pending' ? 'Company Request Submitted Successfully!' : 'Company Created Successfully!';
        return response()->json(['status' => 'success', 'message' => $message]);
    }
    public function show($id)
    {
        $company = Company::with(['parent', 'directors', 'ceos'])->find($id);

        if (!$company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        // 🛡️ OWNERSHIP & GOD-MODE CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        $isGodMode = false;
        // email check ko safe banaya taaki null hone par crash na ho
        if ($user && (in_array($user->email ?? '', $developerEmails) || class_basename($user) === 'SuperAdmin')) {
            $isGodMode = true;
        }

        // Agar God mode nahi hai aur 'company_view' ki permission nahi hai
        // Toh wo sirf wahi company dekh sakta hai jisme wo employed hai
        if (!$isGodMode && (!$user || !$user->can('company_view'))) {
            if ($company->id != ($user->company_id ?? 0)) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You can only access your own company details.'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $company]);
    }

    public function update(Request $request, $id)
    {
        $company = Company::find($id);

       $user = auth()->user();

// Pehle permission check karein
if (!$user->can('company_edit')) {
    // Agar edit permission nahi hai, toh check karein ki kya wo apni khud ki company update kar raha hai?
    if ($company->id != $user->company_id) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You can only access your own company.'], 403);
    }
}

        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_code' => 'required|string|max:10|unique:companies,company_code,' . $id,
            'cin_no'       => 'required|string|max:255',
            'company_logo' => 'nullable|mimes:jpeg,png,jpg,gif,webp,bmp|max:5120'
        ]);

        if ($request->parent_id == $id) {
            return response()->json(['status' => 'error', 'message' => 'A company cannot be its own parent!'], 400);
        }

        $logoPath = $company->company_logo;

        // 🔥 UNIVERSAL LOGO UPDATE LOGIC
        if ($request->hasFile('company_logo')) {
            $converter = new MediaConverterService();
            $media = $converter->uploadAndConvert($request->file('company_logo'));

            if ($media) {
                if ($logoPath && File::exists(public_path($logoPath))) {
                    File::delete(public_path($logoPath));
                }
                $logoPath = $media->file_path;
            }
        } elseif ($request->remove_logo_flag == '1') {
            if ($logoPath && File::exists(public_path($logoPath))) {
                File::delete(public_path($logoPath));
            }
            $logoPath = null;
        }

        $oldStatus = $company->status;
        $newStatus = $request->status ?? 'active';

        // 🔥 NAYA: Extract Location for Update
        $mapData = $this->extractLatLng($request->map_url);

        $company->update([
            'company_name'  => $request->company_name,
            'company_code'  => strtoupper($request->company_code),
            'cin_no'        => strtoupper($request->cin_no),
            'iso_no'        => $request->iso_no,
            'trademark'     => $request->trademark,
            'logo_reg_no'   => $request->logo_reg_no,
            'parent_id'     => $request->parent_id ?: null,
            'phone'         => $request->phone,
            'email'         => $request->email,
            'state'         => $request->state,
            'district'      => $request->district,
            'address'       => $request->address,
            'gst_no'        => $request->gst_no,
            'status'        => $newStatus,
            'company_logo'  => $logoPath,

            // 🔥 NAYA: Database fields update karein
            'map_url'       => $request->map_url,
            'latitude'      => $mapData['latitude'],
            'longitude'     => $mapData['longitude']
        ]);

        // 🔥 NAYA: JSON PAYLOAD UPDATE LOGIC 🔥
        if ($request->has('board_assignments')) {
            $boardData = json_decode($request->board_assignments, true);

            // Purane records delete karo pivot table se
            DB::table('company_director')->where('company_id', $company->id)->delete();

            $insertData = [];
            foreach ($boardData as $board) {
                $insertData[] = [
                    'company_id'  => $company->id,
                    'director_id' => $board['director_id'] ?? null,
                    'ceo_id'      => $board['ceo_id'] ?? null,
                    'role'        => $board['role'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            if (!empty($insertData)) {
                DB::table('company_director')->insert($insertData);
            }
        }

        if ($oldStatus === 'active' && $newStatus === 'inactive') {
            Company::where('parent_id', $id)->update(['status' => 'inactive']);
            \App\Models\Branch::where('company_id', $id)->update(['branch_status' => 'inactive']);
        }

        return response()->json(['status' => 'success', 'message' => 'Company Updated Successfully!']);
    }

    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['status' => 'error', 'message' => 'Company not found'], 404);
        }

        $user = auth()->user();
if (!$user->can('company_delete')) {
    return response()->json(['status' => 'error', 'message' => 'Unauthorized! You do not have permission to delete companies.'], 403);
}

        Company::destroy($id);

        return response()->json(['status' => 'success', 'message' => 'Company Deleted Successfully']);
    }

    public function getActiveCompanies()
    {
        $query = Company::where('status', 'active');

        // 🛡️ DATA FILTER LOGIC
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!in_array($user->email, $developerEmails)) {
            $query->where('id', $user->company_id);
        }

        $companies = $query->get();
        return response()->json(['status' => 'success', 'data' => $companies]);
    }

    // 🔥 NAYA: Google Map Link/Iframe se Lat-Lng extract karne ka function
    private function extractLatLng($mapString)
    {
        if (empty($mapString)) {
            return ['latitude' => null, 'longitude' => null];
        }

        // 1. Iframe ya Embed URL check (pb parameter me !3d aur !2d/!4d hota hai)
        if (strpos($mapString, '<iframe') !== false || strpos($mapString, 'pb=') !== false) {
            preg_match('/!3d([-0-9.]+)/', $mapString, $latMatch);
            // Longitude kabhi !2d hota hai aur kabhi !4d
            preg_match('/![24]d([-0-9.]+)/', $mapString, $lngMatch); 

            if (isset($latMatch[1]) && isset($lngMatch[1])) {
                return ['latitude' => $latMatch[1], 'longitude' => $lngMatch[1]];
            }
        }

        // 2. Normal URL '@lat,lng' format (eg: google.com/maps/@26.12,85.34,15z)
        if (preg_match('/@([-0-9.]+),([-0-9.]+)/', $mapString, $matches)) {
            return ['latitude' => $matches[1], 'longitude' => $matches[2]];
        }

        // 3. Query parameter 'q=lat,lng' format
        if (preg_match('/[?&]q=([-0-9.]+),([-0-9.]+)/', $mapString, $matches)) {
            return ['latitude' => $matches[1], 'longitude' => $matches[2]];
        }

        return ['latitude' => null, 'longitude' => null];
    }
}
