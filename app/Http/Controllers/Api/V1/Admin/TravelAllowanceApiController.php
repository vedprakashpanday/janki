<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TravelAllowance;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemAlertNotification;
use App\Helpers\NotificationHelper;
use App\Services\MediaConverterService;

class TravelAllowanceApiController extends Controller
{
    protected $mediaConverter;

    public function __construct(MediaConverterService $mediaConverter)
    {
        $this->mediaConverter = $mediaConverter;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = TravelAllowance::with(['company', 'branch', 'employee', 'approver'])->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('purpose', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('destination', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('vehicle_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('person_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('person_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('employee', function ($q2) use ($searchTerm) {
                        $q2->where('full_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('member_id', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $emailStr = strtolower($user->email ?? '');
        $isGodMode = in_array($emailStr, $developerEmails);

        $isExecutive = !empty($user->designation_name) && (str_contains(strtolower($user->designation_name), 'ceo') || str_contains(strtolower($user->designation_name), 'director'));
        $canApproveReject = $user->hasPermissionTo('ta_appr') || $user->hasPermissionTo('ta_rej');

        if (!$isGodMode) {
            if ($isExecutive || $canApproveReject) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->where('employee_id', $user->id);
            }
        }

        return response()->json($query->paginate($request->input('per_page', 10)));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'ta_date' => 'required|date',
            'employee_id' => 'required|exists:adm_regist,id',
            'company_id' => 'required|exists:companies,id',
            'amount' => 'required|numeric',
            'number_of_persons' => 'required|integer|min:1',
            // Files check will be handled in loop
        ]);

        // 🔥 MULTIPLE FILES UPLOAD LOGIC 🔥
        $proofFilesPaths = [];
        if ($request->hasFile('proof_files')) {
            foreach ($request->file('proof_files') as $file) {
                $mediaRecord = $this->mediaConverter->uploadAndConvert($file);
                if ($mediaRecord) {
                    $proofFilesPaths[] = $mediaRecord->file_path;
                } else {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    $file->move(public_path('uploads/ta_proofs'), $filename);
                    $proofFilesPaths[] = 'uploads/ta_proofs/' . $filename;
                }
            }
        }

        $branchId = ($request->branch_id === 'HO') ? null : $request->branch_id;

        $ta = TravelAllowance::create([
            'company_id' => $request->company_id,
            'branch_id' => $branchId,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'employee_id' => $request->employee_id,
            'ta_date' => $request->ta_date,
            'vehicle_no' => $request->vehicle_no,
            'purpose' => $request->purpose,
            'destination' => $request->destination,
            'distance_km' => $request->distance_km,
            'in_time' => $request->in_time,
            'out_time' => $request->out_time,
            'fuel_litre' => $request->fuel_litre,
            'amount' => $request->amount,
            'person_name' => $request->person_name,
            'person_number' => $request->person_number,
            'number_of_persons' => $request->number_of_persons ?? 1,
            'purpose' => 'required|string|min:200',
            'proof_file' => count($proofFilesPaths) > 0 ? json_encode($proofFilesPaths) : null,
            'status' => 'pending'
        ]);

        $targets = NotificationHelper::getTargets($request->company_id, $branchId, 'ta_appr');
        $targets = $targets->reject(function ($target) use ($user) {
            return $target->id === $user->id;
        });

        if ($targets->count() > 0) {
            Notification::send($targets, new SystemAlertNotification(
                "New TA Request",
                "TA Request of ₹{$ta->amount} submitted by " . ($user->full_name ?? 'Employee'),
                "/admin/travel-allowances",
                "fa-car-side",
                "text-primary"
            ));
        }

        return response()->json(['message' => 'TA request submitted successfully.', 'data' => $ta], 201);
    }

    public function update(Request $request, $id)
    {
        $ta = TravelAllowance::findOrFail($id);
        if ($ta->status !== 'pending') return response()->json(['message' => 'Cannot edit processed request.'], 403);

        // 🔥 NAYA: Edit me bhi 200 char limit
        $request->validate([
            'purpose' => 'required|string|min:200',
        ]);

        $data = $request->except(['proof_files', 'existing_proofs']);

        // 🔥 EDIT MODE: EXISTING + NEW FILES MERGE LOGIC 🔥
        $proofFilesPaths = [];
        if ($request->filled('existing_proofs')) {
            $existing = json_decode($request->existing_proofs, true);
            if (is_array($existing)) {
                $proofFilesPaths = $existing; // Purani files rakh li
            }
        }

        if ($request->hasFile('proof_files')) {
            foreach ($request->file('proof_files') as $file) {
                $mediaRecord = $this->mediaConverter->uploadAndConvert($file);
                if ($mediaRecord) {
                    $proofFilesPaths[] = $mediaRecord->file_path;
                } else {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    $file->move(public_path('uploads/ta_proofs'), $filename);
                    $proofFilesPaths[] = 'uploads/ta_proofs/' . $filename;
                }
            }
        }

        $data['proof_file'] = count($proofFilesPaths) > 0 ? json_encode($proofFilesPaths) : null;
        $data['branch_id'] = ($request->branch_id === 'HO') ? null : $request->branch_id;

        $ta->update($data);
        return response()->json(['message' => 'TA request updated.']);
    }

    public function approve(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $user = Auth::user();

        if (!$context->is_god && !$context->is_director && !$user->hasPermissionTo('ta_appr')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $ta = TravelAllowance::findOrFail($id);
        $approvedAmount = $request->input('approved_amount', $ta->amount);
        $remarks = $request->input('remarks', $ta->remarks);

        // 🔥 EXACT APPROVER ROLE LOGIC 🔥
        $roleName = 'HR Management';
        if ($context->role_level === 'ceo') {
            $roleName = 'Super Admin';
        } elseif ($context->is_director) {
            $roleName = 'Director Management';
        } elseif ($context->is_god) {
            $roleName = 'HR Management'; // admin@jankivilla.com ke liye
        }

        $ta->update([
            'status' => 'active',
            'approver_id' => $context->is_god ? null : $user->id,
            'approver_role' => $roleName,
            'approved_amount' => $approvedAmount,
            'remarks' => $remarks
        ]);

        if ($ta->employee) {
            $ta->employee->notify(new SystemAlertNotification(
                "TA Request Approved / Updated",
                "Your TA was approved/updated for ₹{$approvedAmount}.",
                "/employee/travel-allowances",
                "fa-check-circle",
                "text-success"
            ));
        }

        return response()->json(['message' => 'TA request approved/updated successfully.']);
    }

    public function reject(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $user = Auth::user();

        if (!$context->is_god && !$context->is_director && !$user->hasPermissionTo('ta_rej')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $ta = TravelAllowance::findOrFail($id);
        $remarks = $request->input('remarks', $ta->remarks);

        // 🔥 EXACT REJECTER ROLE LOGIC 🔥
        $roleName = 'HR Management';
        if ($context->role_level === 'ceo') {
            $roleName = 'Super Admin';
        } elseif ($context->is_director) {
            $roleName = 'Director Management';
        } elseif ($context->is_god) {
            $roleName = 'HR Management'; // admin@jankivilla.com ke liye
        }

        $ta->update([
            'status' => 'rejected',
            'approver_id' => $context->is_god ? null : $user->id,
            'approver_role' => $roleName,
            'remarks' => $remarks
        ]);

        if ($ta->employee) {
            $ta->employee->notify(new SystemAlertNotification(
                "TA Request Rejected",
                "Your TA request was rejected.",
                "/employee/travel-allowances",
                "fa-times-circle",
                "text-danger"
            ));
        }

        return response()->json(['message' => 'TA request rejected successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        TravelAllowance::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Records deleted.']);
    }

    public function destroy($id)
    {
        TravelAllowance::findOrFail($id)->delete();
        return response()->json(['message' => 'Record deleted.']);
    }

    public function updateRemarks(Request $request, $id)
    {
        $ta = TravelAllowance::findOrFail($id);
        $ta->update(['remarks' => $request->remarks]);
        return response()->json(['message' => 'Remarks saved.', 'remarks' => $ta->remarks]);
    }

    public function printPreview($id)
    {
        $ta = TravelAllowance::with(['company', 'branch', 'employee', 'approver'])->findOrFail($id);
        return view('admin.travel_allowances.print', ['ta' => $ta, 'company' => $ta->company, 'branch' => $ta->branch]);
    }

    public function show($id)
    {
        $ta = TravelAllowance::with(['company', 'branch', 'employee', 'approver'])->findOrFail($id);
        $html = view('admin.travel_allowances.view_partial', ['ta' => $ta, 'company' => $ta->company, 'branch' => $ta->branch])->render();
        return response()->json(['html' => $html]);
    }
}
