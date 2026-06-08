<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WelcomeLetterTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WelcomeLetterWebController extends Controller
{
    // 🔥 EMPLOYEE PANEL LILYE: Welcome Letter Dikhane Ka Function
    public function showEmployeeLetter()
    {
        $user = Auth::user();
        
        // Fetch Template (Agar admin ne nahi banaya to default null hoga, par hum code mein handle karenge)
        $template = WelcomeLetterTemplate::where('letter_type', 'employee')->first();
        
        // Default Template Content (Agar database khali ho)
        $content = $template ? $template->content : $this->getDefaultTemplate();

        // Getting Dynamic Data from linked tables
        $company = DB::table('companies')->where('id', $user->company_id)->first();
        $department = DB::table('departments')->where('id', $user->department_id)->first();
        $designation = DB::table('designations')->where('id', $user->designation_id)->first();
        $branch = DB::table('branches')->where('id', $user->branch_id)->first();

        // Preparing Replacement Variables
        $replacements = [
            '[DATE]' => date('d-m-Y'),
            '[EMPLOYEE_NAME]' => $user->name ?? $user->full_name ?? 'Employee',
            '[FATHER_NAME]' => $user->father_name ?? $user->guardian_name ?? '_________________',
            '[ADDRESS]' => $user->address ?? '_________________________________',
            '[EMP_ID]' => $user->emp_id ?? $user->employee_code ?? 'EMP-_____',
            '[COMPANY_NAME]' => $company->company_name ?? 'Amitabh Builders And Developers Pvt. Ltd.',
            '[DEPARTMENT]' => $department->department_name ?? 'Operations',
            '[DESIGNATION]' => $designation->designation_name ?? 'Executive',
            '[BRANCH_NAME]' => $branch->branch_name ?? 'Head Office',
        ];

        // Replacing placeholders with actual data
        foreach ($replacements as $placeholder => $actualValue) {
            $content = str_replace($placeholder, $actualValue, $content);
        }

        return view('employee.welcome_letter', compact('content'));
    }

    // Default template fallback with dynamic Placeholders
    private function getDefaultTemplate()
    {
        return '
        <div class="text-end mb-4"><strong>Date:</strong> [DATE]</div>
        <h2 class="text-center letter-title mb-4">WELCOME LETTER</h2>
        
        <div class="mb-4">
            <p><strong>To,</strong><br>
            <strong>Mr./Mrs./Ms.:</strong> [EMPLOYEE_NAME]<br>
            <strong>Father’s / Husband’s Name:</strong> [FATHER_NAME]<br>
            <strong>Address:</strong> [ADDRESS]</p>
        </div>

        <div class="mb-4 p-3 bg-light border-start border-4 border-warning">
            <strong>Employee Identification & Professional Details</strong><br>
            Employee ID: <strong>[EMP_ID]</strong><br>
            Department: <strong>[DEPARTMENT]</strong><br>
            Designation: <strong>[DESIGNATION]</strong>
        </div>

        <p>Dear Sir / Madam,</p>
        <p>Warm greetings from <strong>[COMPANY_NAME]</strong>.</p>
        
        <p>We are extremely pleased that you have chosen to place your trust in our company for your career. We sincerely thank you for deciding to associate with us. Your trust marks the beginning of a long-term relationship built on transparency, confidence, and mutual cooperation.</p>
        
        <p>Our objective is to provide reliable, well-planned, and affordable real estate opportunities so that every individual and family can achieve the dream of owning land or a home. In all our operations, we prioritize the highest standards of transparency, legal compliance, and customer satisfaction.</p>

        <p>Your association with us is very important. Our team will always be ready to assist and support you in all your professional endeavors.</p>

        <p>We firmly believe that every team member is an important part of our growing family. Your trust motivates us to continuously develop better projects and deliver excellent services.</p>

        <p>Once again, we warmly welcome you to the <strong>[COMPANY_NAME]</strong> family and wish you a bright future, prosperity, and continued success.</p>
        
        <div class="mt-5 pt-4 d-flex justify-content-between align-items-end">
            <div>
                <p class="mb-0">Sincerely,</p>
                <strong>[COMPANY_NAME]</strong>
            </div>
            <div class="text-center">
                <hr style="width: 200px; border-color: #333;" class="mb-1">
                Authorized Signatory<br>
                <small class="text-muted">(Director / HR Head)</small>
            </div>
        </div>';
    }
}