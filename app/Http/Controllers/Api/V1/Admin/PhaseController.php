<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Phase;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Services\MediaConverterService; // Aapka Custom Service Import
use App\Http\Controllers\Controller;

class PhaseController extends Controller
{
    public function index()
    {
        $context = $this->getGlobalContext();
        $query = Phase::query();

        if ($context->is_god) {
            // God sees all
        } elseif ($context->is_director) {
            $query->where('company_id', $context->company_id);
        } else {
            $query->where('company_id', $context->company_id)
                  ->where('branch_id', $context->branch_id);
        }

        $phases = $query->latest()->get();
        return view('phases.index', compact('phases', 'context'));
    }

    public function create()
    {
        $context = $this->getGlobalContext();
        
        $companies = Company::all(); 
        $branches = [];
        
        if ($context->is_director) {
            $branches = Branch::where('company_id', $context->company_id)->get();
        } elseif ($context->is_god) {
            $branches = Branch::all();
        } else {
            $branches = Branch::where('id', $context->branch_id)->get();
        }

        return view('phases.create', compact('context', 'companies', 'branches'));
    }

    public function store(Request $request, MediaConverterService $mediaService)
    {
        $context = $this->getGlobalContext();

        $rules = [
            'phase_name' => 'required|string|max:255',
            'phase_location' => 'required|string|max:255',
            'phase_details' => 'required|string',
            'phase_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,bmp|max:5120', // Up to 5MB allow karo, service compress kar dega
            'phase_google_map_url' => 'nullable|url'
        ];

        if ($context->is_god) {
            $rules['company_id'] = 'required|integer';
            $rules['branch_id'] = 'required|integer';
        } elseif ($context->is_director) {
            $rules['branch_id'] = 'required|integer';
        }

        $request->validate($rules);

        $data = $request->only([
            'phase_name', 'phase_location', 'phase_details', 'phase_google_map_url'
        ]);

        if ($context->is_god) {
            $data['company_id'] = $request->company_id;
            $data['branch_id'] = $request->branch_id;
        } elseif ($context->is_director) {
            $data['company_id'] = $context->company_id;
            $data['branch_id'] = $request->branch_id;
        } else {
            $data['company_id'] = $context->company_id;
            $data['branch_id'] = $context->branch_id;
        }

        $data['created_by'] = $context->profile_id;

        // 🔥 Aapki Media Converter Service ka magic yahan chalega 🔥
        if ($request->hasFile('phase_image')) {
            $media = $mediaService->uploadAndConvert($request->file('phase_image'));
            
            // Agar convert hokar database(Media model) me save ho gaya, toh uska path phase me daal do
            if ($media) {
                $data['phase_image'] = $media->file_path; 
            }
        }

        Phase::create($data);

        return redirect()->route('phases.index')->with('success', 'Phase added successfully.');
    }
}