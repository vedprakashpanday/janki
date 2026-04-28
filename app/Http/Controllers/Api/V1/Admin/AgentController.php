<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Branch;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::with('branch')->orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $agents]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required',
            'contact_no' => 'required',
            'joining_date' => 'required|date'
        ]);

        $data = $request->except(['_token']);

        // 1. Branch ID Logic (e.g., AG/BR/DBG1/01/2026)
        $branch = Branch::findOrFail($request->branch_id);
        $branchParts = explode('/', $branch->branch_id); 
        
        $stateCode = $branchParts[1] ?? 'ST';
        $distCode  = $branchParts[2] ?? 'DIST';
        $year = date('Y', strtotime($request->joining_date));

        // Get Highest Sequence Number
        $lastAgent = Agent::where('branch_id', $branch->id)->orderBy('id', 'desc')->first();
        if ($lastAgent && $lastAgent->agent_id) {
            $lastIdParts = explode('/', $lastAgent->agent_id);
            $lastSeq = (int) ($lastIdParts[3] ?? 0); 
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        $sequence = str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        $data['agent_id'] = "AG/{$stateCode}/{$distCode}/{$sequence}/{$year}";

        // 2. Password Generation (Name@Aadhar)
        $firstName = explode(' ', $request->full_name)[0];
        $namePart = ucfirst(strtolower(substr($firstName, 0, 3)));
        $aadharPart = substr(preg_replace('/\D/', '', $request->aadhar_no ?? '0000'), -4);
        $data['password'] = $namePart . '@' . str_pad($aadharPart, 4, '0', STR_PAD_LEFT);

        // 3. File Uploads Loop (22 Docs)
        $fileFields = [
            'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'passport_photo', 
            'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf', 'other_pdf',
            'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 
            'nom_passport_photo', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf', 'nom_other_pdf'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/agents'), $filename);
                $data[$field] = 'uploads/agents/' . $filename;
            }
        }

        // If active, clear leaving details just in case
        if(($data['agent_status'] ?? 'active') == 'active'){
            $data['d_o_l'] = null;
            $data['leaving_remarks'] = null;
        }

        $agent = Agent::create($data);

        return response()->json([
            'status' => 'success', 
            'message' => "Agent saved! ID: {$agent->agent_id} | Pass: {$agent->password}"
        ]);
    }

    public function show($id)
    {
        return response()->json(['status' => 'success', 'data' => Agent::with('branch')->findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);
        $data = $request->except(['_token', 'agent_id', '_method']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $fileFields = [ /* Same 22 fields */
            'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'passport_photo', 
            'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf', 'other_pdf',
            'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 
            'nom_passport_photo', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf', 'nom_other_pdf'
        ];
        
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/agents'), $filename);
                $data[$field] = 'uploads/agents/' . $filename;
            }
        }

        if(($data['agent_status'] ?? 'active') == 'active'){
            $data['d_o_l'] = null;
            $data['leaving_remarks'] = null;
        }

        $agent->update($data);
        return response()->json(['status' => 'success', 'message' => 'Agent updated successfully']);
    }

    public function destroy($id)
    {
        Agent::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }
}