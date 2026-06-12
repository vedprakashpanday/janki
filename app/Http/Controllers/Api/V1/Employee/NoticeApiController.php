<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice;
use App\Models\NoticeReply;
use App\Models\Company;
use Carbon\Carbon;

class NoticeApiController extends Controller
{
   
  public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        if (!$context) return response()->json(['success' => false], 401);

        $audienceType = '';
        if ($context->is_employee) $audienceType = 'employee';
        elseif ($context->is_member) $audienceType = 'member';
        elseif ($context->is_customer) $audienceType = 'customer';

        $profileId = $context->profile_id;
        $userCompanyId = $context->company_id;
        $userBranchId = $context->branch_id;
        $userDeptId = $context->department_id;

        $notices = Notice::where('status', 'active')
            ->where(function($query) use ($userCompanyId) {
                // Lock to User's Company or Global Notices
                $query->where('target_company_id', $userCompanyId)
                      ->orWhereNull('target_company_id');
            })
            ->where(function($query) use ($userBranchId, $userDeptId, $audienceType, $profileId) {
                
                // 1. HIERARCHY MATCHING (Branch & Dept)
                $query->where(function($hq) use ($userBranchId, $userDeptId) {
                    $hq->where(function($bq) use ($userBranchId) {
                        $bq->whereNull('target_branch_id')
                           ->orWhere('target_branch_id', $userBranchId);
                    });
                    $hq->where(function($dq) use ($userDeptId) {
                        $dq->whereNull('target_department_id')
                           ->orWhere('target_department_id', $userDeptId);
                    });
                });

                // 2. AUDIENCE MATCHING & DIRECTOR-ONLY LOGIC
                $query->where(function($aq) use ($audienceType, $profileId) {
                    
                    // Condition A: Individual Specific Person
                    $aq->where(function($sq) use ($audienceType, $profileId) {
                        $sq->where('target_audience', 'other')
                           ->where('entity_type', $audienceType)
                           ->where('entity_id', $profileId);
                    });

                    // Condition B: Explicit Audience Match (e.g., Only Employees)
                    $aq->orWhere('target_audience', $audienceType);

                    // Condition C: Target Audience is 'All'
                   $aq->orWhere(function($allQ) {
                        $allQ->where('target_audience', 'all')
                             // 🔥 FIX: Agar company null hai (Global Notice) toh sabko dikhao.
                             // Agar company di gayi hai par branch/dept null hain, toh hi block karo (Director only)
                             ->where(function($dirCheck) {
                                 $dirCheck->whereNull('target_company_id') // Global bypass
                                          ->orWhereNotNull('target_branch_id')
                                          ->orWhereNotNull('target_department_id');
                             });
                    });
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $notices]);
    }
    // 2. Fetch Notice Details (with Header & Watermark like Welcome Letter)
    public function show($id)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();
        
        $notice = Notice::findOrFail($id);

        $company = $user->company ?? Company::find($context->company_id ?? 1);
        $branch = $user->branch ?? null;
        $companyName = $company ? strtoupper($company->company_name) : 'AMITABH BUILDERS & DEVELOPERS PVT. LTD.';

        // 🔥 HEADER SERVER SIDE RENDERING 🔥
        $headerHtml = view('components.print-header', [
            'company' => $company,
            'branch'  => $branch
        ])->render();

        // 🔥 WATERMARK SERVER SIDE RENDERING 🔥
        $logoUrl = ($company && !empty($company->company_logo)) ? asset($company->company_logo) : "https://ui-avatars.com/api/?name=" . urlencode($companyName) . "&color=7F9CF5&background=EBF4FF";

        $watermarkHtml = '
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.08; z-index: 0; pointer-events: none; text-align: center; width: 100%;">
            <img src="' . $logoUrl . '" style="width: 100%; max-width: 480px; height: auto; margin: 0 auto; filter: grayscale(100%);">
        </div>';

        // Notice Body Formatting
        $noticeDate = Carbon::parse($notice->notice_date)->format('d-m-Y');
        $noticeBody = '<div style="margin-bottom:20px; text-align:right; font-size:15px;"><strong>Date:</strong> ' . $noticeDate . '</div>';
        $noticeBody .= '<div style="margin-bottom:20px; text-align:center;"><h3 style="color:#1A365D; border-bottom:2px solid #D69E2E; display:inline-block; padding-bottom:5px; font-weight:bold;">' . strtoupper($notice->title) . '</h3></div>';
        $noticeBody .= '<div style="font-size:15px; line-height:1.6;">' . $notice->content . '</div>';

        $finalHtml = $watermarkHtml . $headerHtml . '<div style="position: relative; z-index: 1;" class="mt-4 pt-2 border-top">' . $noticeBody . '</div>';

        // Check if user has already replied
        $hasReplied = false;
        if ($notice->requires_reply == 1) {
            $hasReplied = NoticeReply::where('notice_id', $id)
                            ->where('sender_id', $context->profile_id)
                            ->exists();
        }

        return response()->json([
            'success'     => true,
            'html'        => $finalHtml,
            'notice'      => $notice,
            'has_replied' => $hasReplied
        ]);
    }

    // 3. Submit Reply from User
    public function submitReply(Request $request, $id)
    {
        $request->validate(['reply_text' => 'required']);
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // Get Name based on who is logged in
        $senderName = $user->full_name ?? $user->employee_name ?? $user->member_name ?? $user->customer_name ?? $user->name ?? 'User';
        
        $senderType = '';
        if ($context->is_employee) $senderType = 'employee';
        elseif ($context->is_member) $senderType = 'member';
        elseif ($context->is_customer) $senderType = 'customer';
        else $senderType = 'admin';

        NoticeReply::create([
            'notice_id'   => $id,
            'sender_type' => $senderType,
            'sender_id'   => $context->profile_id,
            'sender_name' => $senderName,
            'reply_text'  => $request->reply_text,
        ]);

        return response()->json(['success' => true, 'message' => 'Your reply has been submitted to the Admin successfully!']);
    }
}