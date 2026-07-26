<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WelcomeLetterTemplate;
use Carbon\Carbon;

class WelcomeLetterApiController extends Controller
{
    public function getLetter()
    {
        $context = $this->getGlobalContext();
        if (!$context) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Access'], 401);
        }

        $user = auth()->user();
        $letterContent = '';
        $replacements = [];

        $currentDate = Carbon::now()->format('d-m-Y');

        // 🔥 YAHAN FIX KIYA HAI: Column conflict ko bypass karke relationship object ko force call kiya
        $company = $user->company()->first();
        $branch = $user->branch()->first(); // String ki jagah Branch Model aayega

        $companyName = $company ? strtoupper($company->company_name) : 'AMITABH BUILDERS & DEVELOPERS PVT. LTD.';
        $branchName = $branch ? strtoupper($branch->branch_location ?? $branch->branch_name) : 'HEAD OFFICE';

        // 2. CHECK PORTAL TYPE AND PREPARE DATA
        if ($context->is_employee) {
            $entityId = $user->member_id ?? $user->id;
            $template = WelcomeLetterTemplate::where('letter_type', 'other')->where('entity_type', 'employee')->where('entity_id', $entityId)->first()
                ?? WelcomeLetterTemplate::where('letter_type', 'employee')->first();

            $letterContent = $template ? $template->content : '';

            $designationObj = $user->designation()->first();
            $exactDesignationName = $designationObj ? $designationObj->designation_name : 'STAFF';

            $replacements = [
                '[EMPLOYEE_NAME]' => strtoupper($user->full_name ?? $user->employee_name ?? 'Team Member'),
                '[EMP_ID]'        => $entityId,
                '[COMPANY_NAME]'  => $companyName,
                '[BRANCH_NAME]'   => $branchName,
                '[DEPARTMENT]'    => $user->department ? strtoupper($user->department->department_name) : 'N/A',
                '[DESIGNATION]'   => strtoupper($exactDesignationName),
                '[ADDRESS]'       => strtoupper($user->communication_address ?? $user->address ?? $user->present_address ?? 'N/A'),
                '[DATE]'          => $currentDate
            ];
        } elseif ($context->is_member) {
            $entityId = $user->member_id ?? $user->id;
            $template = WelcomeLetterTemplate::where('letter_type', 'other')->where('entity_type', 'member')->where('entity_id', $entityId)->first()
                ?? WelcomeLetterTemplate::where('letter_type', 'member')->first();

            $letterContent = $template ? $template->content : '';

            // Tumhari requirement ke hisab se designation_id se uthaya
            $designationObj = $user->designation()->first();
            $exactDesignationName = $designationObj ? $designationObj->designation_name : 'ASSOCIATE';

            $replacements = [
                '[MEMBER_NAME]'  => strtoupper($user->full_name ?? $user->member_name ?? 'Associate Member'),
                '[MEMBER_ID]'    => $entityId,
                '[COMPANY_NAME]' => $companyName,
                '[SPONSOR_ID]'   => $user->sponsor_id ?? 'DIRECT',
                '[DESIGNATION]'  => strtoupper($exactDesignationName),
                '[ADDRESS]'      => strtoupper($user->address ?? $user->present_address ?? 'N/A'),
                '[DATE]'         => $currentDate
            ];
        } elseif ($context->is_customer) {
            $entityId = $user->customer_id ?? $user->id;
            $template = WelcomeLetterTemplate::where('letter_type', 'other')->where('entity_type', 'customer')->where('entity_id', $entityId)->first()
                ?? WelcomeLetterTemplate::where('letter_type', 'customer')->first();

            $letterContent = $template ? $template->content : '';

            $replacements = [
                '[CUSTOMER_NAME]' => strtoupper($user->customer_name ?? 'Valued Customer'),
                '[FATHER_NAME]'   => strtoupper($user->father_name ?? $user->husband_name ?? 'N/A'),
                '[ADDRESS]'       => $user->address ?? 'N/A',
                '[CUSTOMER_ID]'   => $entityId,
                '[DATE]'          => $currentDate
            ];
        }

        if (empty($letterContent)) {
            return response()->json(['success' => false, 'message' => 'Welcome letter template not configured.']);
        }

        // 3. TAG REPLACEMENT
        $letterContent = str_replace(array_keys($replacements), array_values($replacements), $letterContent);

        // 4. HEADER SERVER SIDE RENDERING
        $headerHtml = view('components.print-header', [
            'company' => $company,
            'branch' => $branch // Ab print-header me string nahi, balki Model ja raha hai
        ])->render();

        // 5. WATERMARK SERVER SIDE RENDERING
        $logoUrl = '';
        if ($company && !empty($company->company_logo)) {
            $logoUrl = asset($company->company_logo);
        } else {
            $logoUrl = "https://ui-avatars.com/api/?name=" . urlencode($companyName) . "&color=7F9CF5&background=EBF4FF";
        }

        $watermarkHtml = '
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.08; z-index: 0; pointer-events: none; text-align: center; width: 100%;">
            <img src="' . $logoUrl . '" style="width: 100%; max-width: 480px; height: auto; margin: 0 auto; ">
        </div>';

        $finalHtml = $watermarkHtml . $headerHtml . '<div style="position: relative; z-index: 1;" class="mt-4 pt-2 border-top">' . $letterContent . '</div>';

        return response()->json([
            'success' => true,
            'data'    => $finalHtml
        ]);
    }
}
