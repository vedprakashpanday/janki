<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WelcomeLetterTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade; // 🔥 Component render karne ke liye add kiya

class WelcomeLetterApiController extends Controller
{
    public function getLetter(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $template = WelcomeLetterTemplate::where('letter_type', 'employee')->first();
        $content = ($template && !empty(trim($template->content))) ? $template->content : $this->getDefaultTemplate();

        $company = DB::table('companies')->where('id', $user->company_id)->first();
        $branchObj = DB::table('branches')->where('id', $user->branch_id)->first(); 

        // 🔥 NAYA: Dynamic Logo path nikalna watermark ke liye
        $logoUrl = ($company && !empty($company->company_logo)) 
                   ? asset($company->company_logo) 
                   : asset('uploads/harihomes1-logo.png'); // Default fallback agar logo na ho

        // Branch Logic
        $branchName = '_________________';
        if (!empty($user->company_id)) {
            $bId = isset($user->branch_id) ? trim(strtolower($user->branch_id)) : '';
            if ($bId === '' || $bId === 'ho' || $bId === 'head office') {
                $branchName = 'Head Office';
            } else {
                $branchName = $branchObj->branch_name ?? '_________________';
            }
        }

        // Replacements
        $replacements = [
            '[DATE]' => date('d-m-Y'),
            '[EMPLOYEE_NAME]' => $user->name ?? $user->full_name ?? $user->employee_name ?? '__________________________',
            '[EMP_ID]' => $user->member_id ?? $user->id ?? '____________________',
            '[COMPANY_NAME]' => $company->company_name ?? 'Amitabh Builders And Developers Pvt. Ltd.',
            '[BRANCH_NAME]' => $branchName,
            '[DEPARTMENT]' => DB::table('departments')->where('id', $user->department_id)->value('department_name') ?? '_________________',
            '[DESIGNATION]' => DB::table('designations')->where('id', $user->designation_id)->value('designation_name') ?? '_________________',
        ];

        foreach ($replacements as $placeholder => $actualValue) {
            $content = str_replace($placeholder, $actualValue, $content);
        }

        // Render Print Header
        $headerHtml = \Illuminate\Support\Facades\Blade::render('<x-print-header :company="$company" :branch="$branch" />', [
            'company' => $company,
            'branch' => $branchObj
        ]);

        $finalContent = $headerHtml . '<div class="mt-4">' . $content . '</div>';

        // 🔥 NAYA: Response me 'logo' bhi bhej rahe hain
        return response()->json([
            'success' => true, 
            'data' => $finalContent,
            'logo' => $logoUrl
        ]);
    }
    private function getDefaultTemplate()
    {
        return '
        <div class="text-end mb-4" style="font-size: 15px;"><strong>Date:</strong> [DATE]</div>
        
        <h2 class="text-center letter-title mb-4" style="color: #1A365D; border-bottom: 2px solid #D69E2E; display: inline-block; padding-bottom: 5px;">STAFF WELCOME LETTER</h2>

        <div class="my-4 p-4 details-box" style="background-color: #F8FAFC; border-left: 4px solid #1A365D; border-radius: 0 6px 6px 0;">
            <h5 class="mb-3 text-uppercase fw-bold border-bottom pb-2" style="font-size: 14px; color: #1A365D;">Employee Identification Details</h5>
            <div class="row g-2" style="font-size: 14.5px;">
                <div class="col-md-6"><strong>Employee Name:</strong> [EMPLOYEE_NAME]</div>
                <div class="col-md-6"><strong>Employee ID:</strong> [EMP_ID]</div>
                <div class="col-md-6"><strong>Company Name:</strong> [COMPANY_NAME]</div>
                <div class="col-md-6"><strong>Branch Office:</strong> [BRANCH_NAME]</div>
                <div class="col-md-6"><strong>Department:</strong> [DEPARTMENT]</div>
                <div class="col-md-6"><strong>Designation:</strong> [DESIGNATION]</div>
            </div>
        </div>

        <div class="letter-body-text mt-4">
            <p><strong>Dear Team Member,</strong></p>
            <p>At the outset, I extend my warm greetings and heartfelt congratulations to you on joining our organization. It gives me immense pleasure to welcome you to <strong>[COMPANY_NAME]</strong>.</p>
            
            <p>Our organization believes that the true strength of any company lies in its people. Every staff member plays an important role in shaping the growth, reputation, and success of the organization. Your skills, dedication, and commitment will contribute significantly to our collective progress and help us deliver quality services to our customers.</p>
            
            <p>At <strong>[COMPANY_NAME]</strong>, we are committed to building not only successful projects but also a positive and professional work environment where every employee is respected, supported, and encouraged to grow. We believe in teamwork, transparency, and continuous improvement, and we encourage every team member to share ideas, develop their skills, and work with confidence and integrity.</p>
            
            <p>Our goal is to create modern, reliable, and affordable real estate developments that improve the quality of life for our communities. One of our proud developments is the Janki Villa Project, which reflects our commitment to quality, trust, and customer satisfaction.</p>
            
            <p>As a member of our team, you are expected to uphold the values of professionalism, honesty, discipline, and responsibility in your work. Together, we will strive to maintain high standards of service and build long-term relationships with our customers and partners.</p>
            
            <p>At the same time, I would like to assure you that your professional growth and career development are also our responsibility. We believe that when our employees grow, the organization grows stronger. Therefore, we strive to provide a supportive environment where every team member can develop their skills, achieve their goals, and build a secure future.</p>
            
            <p>In addition, the company also believes in the well-being of its employees. <strong>[COMPANY_NAME]</strong> provides Health Insurance Policy benefits to its eligible staff members, ensuring support and security for them and their families during medical needs.</p>
            
            <p class="mt-4 mb-3"><strong>To maintain a professional and organized workplace, all staff members are expected to follow the basic office rules and guidelines:</strong></p>
            
            <ul class="list-unstyled">
                <li class="mb-3"><strong>Discipline & Professional Conduct:</strong> Every employee must maintain punctuality, sincerity, and professional behavior while performing their duties. Respectful communication with colleagues, seniors, and clients is essential for maintaining a healthy work culture.</li>
                <li class="mb-3"><strong>Office Dress Code:</strong> All staff members are expected to follow a proper and decent dress code while attending office or meeting clients. Professional attire reflects the image and credibility of the organization.</li>
                <li class="mb-3"><strong>Office Timings & Attendance:</strong> Employees are expected to report to work on time and maintain regular attendance. Timely completion of assigned tasks and responsibilities is essential for smooth operations.</li>
                <li class="mb-3"><strong>Work Ethics & Responsibility:</strong> Each team member must perform their assigned responsibilities with honesty, dedication, and accountability. Maintaining confidentiality of company information and client details is also very important.</li>
                <li class="mb-3"><strong>Team Cooperation:</strong> A positive work environment is built through teamwork and mutual respect. Employees are encouraged to cooperate with colleagues and support each other to achieve organizational goals.</li>
            </ul>

            <p>We believe that when employees grow, the organization grows with them. Therefore, we encourage you to perform your duties with enthusiasm, dedication, and a positive attitude so that together we can achieve new milestones of success.</p>
            
            <p>Once again, welcome to the organization. We look forward to your valuable contribution and wish you a successful and rewarding career with us.</p>
        </div>

        <div class="mt-5 pt-3">
            <p class="mb-2">With Best Wishes,</p>
            <div class="mt-4">
                <strong>CMD</strong><br>
                <strong>[COMPANY_NAME]</strong><br>
                <small class="text-muted">(Developer of Janki Villa Project)</small>
            </div>
        </div>';
    }
}
