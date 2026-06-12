<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WelcomeLetterTemplate;
use App\Models\Employee;
use App\Models\Member;
use App\Models\Customer;

class WelcomeLetterAdminController extends Controller
{
   // 1. Fetch List for Datalist/Dropdown (For Specific Templates)
    public function getEntities(Request $request)
    {
        $type = $request->query('type'); // employee, member, customer
        $data = [];

        if ($type === 'employee') {
            $data = Employee::select('member_id', 'full_name')->get()->map(function($item) {
                return ['id' => $item->member_id, 'name' => $item->full_name];
            });
        } elseif ($type === 'member') {
            $data = Member::select('member_id', 'full_name')->get()->map(function($item) {
                return ['id' => $item->member_id, 'name' => $item->full_name];
            });
        } elseif ($type === 'customer') {
            $data = Customer::select('customer_id', 'customer_name')->get()->map(function($item) {
                return ['id' => $item->customer_id, 'name' => $item->customer_name];
            });
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    // 2. Fetch Template for Admin Edit Form
    public function getTemplate(Request $request)
    {
        $letterType = $request->query('type', 'employee'); // employee, member, customer, other
        $entityType = $request->query('entity_type');      // if other: employee, member, customer
        $entityId   = $request->query('entity_id');        // if other: EMP001, CUS005 etc.

        // Pehle check karo ki is specific ID ke liye koi custom template bana hai kya
        $template = WelcomeLetterTemplate::where('letter_type', $letterType)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();

        // Agar "Other/Specific" select kiya hai aur us user ka template nahi mila, 
        // toh us category ka Common Template nikalo
        if (!$template && $letterType === 'other' && $entityType) {
            $template = WelcomeLetterTemplate::where('letter_type', $entityType)
                ->whereNull('entity_id')
                ->first();
        }

        if ($template && !empty(trim($template->content))) {
            $content = $template->content;
        } else {
            // Agar database me kuch bhi nahi hai, to Hardcoded Default Load karo
            $targetType = ($letterType === 'other') ? $entityType : $letterType;

            if ($targetType === 'member') {
                $content = $this->getDefaultMemberTemplate();
            } elseif ($targetType === 'customer') {
                $content = $this->getDefaultCustomerTemplate();
            } else {
                $content = $this->getDefaultEmployeeTemplate();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $content
        ]);
    }

    // 3. Save/Update Template
    public function updateTemplate(Request $request)
    {
        $request->validate([
            'content' => 'required',
            'type'    => 'required|in:employee,member,customer,other',
        ]);

        WelcomeLetterTemplate::updateOrCreate(
            [
                'letter_type' => $request->type,
                'entity_type' => $request->entity_type ?? null,
                'entity_id'   => $request->entity_id ?? null,
            ],
            [
                'title'   => ucfirst($request->type) . ' Welcome Letter',
                'content' => $request->content
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Welcome Letter Template updated successfully!'
        ]);
    }

    // =========================================================
    // DEFAULT HARDCODED TEMPLATES (100% Exactly As Provided)
    // =========================================================

private function getDefaultEmployeeTemplate()
    {
        return '
        <div style="text-align: right; margin-bottom: 20px;"><strong>Date:</strong> [DATE]</div>
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="color: #1A365D; border-bottom: 2px solid #D69E2E; display: inline-block; padding-bottom: 5px;">Staff Welcome Letter</h2>
        </div>
        
        <table style="width: 100%; margin-bottom: 25px; border-collapse: collapse; background-color: #F8FAFC; border-left: 4px solid #1A365D;">
            <tbody>
                <tr><td colspan="2" style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>Employee Identification Details</strong></td></tr>
                <tr>
                    <td style="padding: 10px;"><strong>Name:</strong> [EMPLOYEE_NAME]</td>
                    <td style="padding: 10px;"><strong>Emp ID:</strong> [EMP_ID]</td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Department:</strong> [DEPARTMENT]</td>
                    <td style="padding: 10px;"><strong>Designation:</strong> [DESIGNATION]</td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Company:</strong> [COMPANY_NAME]</td>
                    <td style="padding: 10px;"><strong>Branch:</strong> [BRANCH_NAME]</td>
                </tr>
                <tr>
                    <td style="padding: 10px;" colspan="2"><strong>Address:</strong> [ADDRESS]</td>
                </tr>
            </tbody>
        </table>

        <p><strong>Dear Team Member,</strong></p>
        <p>At the outset, I extend my warm greetings and heartfelt congratulations to you on joining our organization. It gives me immense pleasure to welcome you to Amitabh Builders and Developers Pvt. Ltd.</p>
        <p>Our organization believes that the true strength of any company lies in its people. Every staff member plays an important role in shaping the growth, reputation, and success of the organization. Your skills, dedication, and commitment will contribute significantly to our collective progress and help us deliver quality services to our customers.</p>
        <p>At Amitabh Builders and Developers Pvt. Ltd., we are committed to building not only successful projects but also a positive and professional work environment where every employee is respected, supported, and encouraged to grow. We believe in teamwork, transparency, and continuous improvement, and we encourage every team member to share ideas, develop their skills, and work with confidence and integrity.</p>
        <p>Our goal is to create modern, reliable, and affordable real estate developments that improve the quality of life for our communities. One of our proud developments is the Janki Villa Project, which reflects our commitment to quality, trust, and customer satisfaction.</p>
        <p>As a member of our team, you are expected to uphold the values of professionalism, honesty, discipline, and responsibility in your work. Together, we will strive to maintain high standards of service and build long-term relationships with our customers and partners.</p>
        <p>At the same time, I would like to assure you that your professional growth and career development are also our responsibility. We believe that when our employees grow, the organization grows stronger. Therefore, we strive to provide a supportive environment where every team member can develop their skills, achieve their goals, and build a secure future.</p>
        <p>In addition, the company also believes in the well-being of its employees. Amitabh Builders and Developers Pvt. Ltd. provides Health Insurance Policy benefits to its eligible staff members, ensuring support and security for them and their families during medical needs.</p>
        <p>To maintain a professional and organized workplace, all staff members are expected to follow the basic office rules and guidelines:</p>
        <p><strong>Discipline & Professional Conduct</strong><br>Every employee must maintain punctuality, sincerity, and professional behavior while performing their duties. Respectful communication with colleagues, seniors, and clients is essential for maintaining a healthy work culture.</p>
        <p><strong>Office Dress Code</strong><br>All staff members are expected to follow a proper and decent dress code while attending office or meeting clients. Professional attire reflects the image and credibility of the organization.</p>
        <p><strong>Office Timings & Attendance</strong><br>Employees are expected to report to work on time and maintain regular attendance. Timely completion of assigned tasks and responsibilities is essential for smooth operations.</p>
        <p><strong>Work Ethics & Responsibility</strong><br>Each team member must perform their assigned responsibilities with honesty, dedication, and accountability. Maintaining confidentiality of company information and client details is also very important.</p>
        <p><strong>Team Cooperation</strong><br>A positive work environment is built through teamwork and mutual respect. Employees are encouraged to cooperate with colleagues and support each other to achieve organizational goals.</p>
        <p>We believe that when employees grow, the organization grows with them. Therefore, we encourage you to perform your duties with enthusiasm, dedication, and a positive attitude so that together we can achieve new milestones of success.</p>
        <p>Once again, welcome to the organization. We look forward to your valuable contribution and wish you a successful and rewarding career with us.</p>
        <p>With Best Wishes,</p><br>
        <p><strong>Amitabh Builders and Developers Pvt. Ltd.</strong></p>
        ';
    }

    private function getDefaultMemberTemplate()
    {
        return '
        <div style="text-align: right; margin-bottom: 20px;"><strong>Date:</strong> [DATE]</div>
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="color: #1A365D; border-bottom: 2px solid #D69E2E; display: inline-block; padding-bottom: 5px;">Welcome Letter</h2>
        </div>

        <table style="width: 100%; margin-bottom: 25px; border-collapse: collapse; background-color: #F8FAFC; border-left: 4px solid #1A365D;">
            <tbody>
                <tr><td colspan="2" style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>Associate Member Details</strong></td></tr>
                <tr>
                    <td style="padding: 10px;"><strong>Name:</strong> [MEMBER_NAME]</td>
                    <td style="padding: 10px;"><strong>Associate ID:</strong> [MEMBER_ID]</td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Sponsor ID:</strong> [SPONSOR_ID]</td>
                    <td style="padding: 10px;"><strong>Designation:</strong> [DESIGNATION]</td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Company:</strong> [COMPANY_NAME]</td>
                    <td style="padding: 10px;"><strong>Address:</strong> [ADDRESS]</td>
                </tr>
            </tbody>
        </table>

        <p><strong>Dear Marketing Associates,</strong></p>
        <p>At the outset, I extend my warm greetings and best wishes to all of you. It gives me great pleasure to welcome you to our dynamic and forward-looking marketing team at Amitabh Builders and Developers Pvt. Ltd. In today’s fast-evolving real estate market, success belongs to those who remain proactive, innovative, and customer-focused. Our organization firmly believes in empowering individuals who are willing to take initiative, build meaningful relationships, and achieve growth through their dedication, professionalism, and performance.</p>
        <p>As members of our marketing team, you play a vital role in connecting people with their dream homes and promising investment opportunities. Your efforts not only contribute to the company’s expansion and reputation but also help many families realize their aspiration of owning land or a home. In this commission-based marketing model, your success is directly linked with your performance, commitment, and ability to build trust with clients.</p>
        <p>We encourage you to adopt modern marketing practices and make full use of digital tools, data insights, and customer-centric communication. By understanding market trends and maintaining strong relationships with clients, customers, and business partners, you can unlock greater opportunities and maximize your earnings as well as your professional growth.</p>
        <p>At our organization, we strongly believe in the following core values:</p>
        <p><strong>Transparency & Trust</strong><br>We strive to maintain clear and honest communication in every transaction so that our clients and marketing partners feel confident, respected, and secure.</p>
        <p><strong>Technology & Innovation</strong><br>With the use of digital platforms, online booking systems, and modern marketing strategies, we aim to simplify processes, enhance transparency, and improve efficiency for both customers and marketing associates.</p>
        <p><strong>Affordable Housing for All</strong><br>Our vision is to make property ownership accessible to the common man by developing affordable, well-planned, and reliable projects that transform aspirations into reality.</p>
        <p><strong>Compliance with Government Regulations</strong><br>We strictly follow all government rules, RERA norms, and statutory requirements to ensure that every project we offer is legally sound, secure, and trustworthy.</p>
        <p><strong>Opportunities for Growth</strong><br>Our commission-based marketing structure allows every associate to grow according to their effort, dedication, and network. There is no limit to what you can achieve with consistent performance, commitment, and determination.</p>
        <p><strong>Bright Future for Marketing Associates</strong><br>At Amitabh Builders and Developers Pvt. Ltd., we believe that the success of our marketing associates defines the success of the company. With dedication, honesty, and strong client relationships, every marketing associate has the opportunity to build a stable, rewarding, and prosperous future in the real estate industry.</p>
        <p><strong>Professional Ethics & Integrity</strong><br>We expect every marketing associate to uphold the highest standards of professionalism, honesty, and ethical conduct while interacting with clients and partners. Our reputation is built on trust, and every team member plays a vital role in protecting and strengthening that trust.</p>
        <p><strong>Continuous Learning & Skill Development</strong><br>Our organization believes in continuous learning and professional development. Marketing associates are encouraged to enhance their knowledge of market trends, customer behavior, and modern sales strategies to achieve higher levels of performance and personal growth.</p>
        <p><strong>Teamwork & Collaboration</strong><br>Although marketing performance is individually rewarding, we strongly believe that teamwork and collaboration create greater success. By supporting one another and sharing insights, we can collectively achieve extraordinary results.</p>
        <p><strong>Long-Term Customer Relationships</strong><br>Our goal is not only to close transactions but also to build long-term relationships with our customers. A satisfied customer becomes our strongest ambassador and contributes to the sustainable growth of our organization.</p>
        <p><strong>Employee Welfare & Health Support</strong><br>We also believe that the well-being of our team members is essential for long-term success. Therefore, the company provides Health Insurance Policy benefits to eligible team members so that they and their families feel secure and supported during medical needs.</p>
        <p><strong>Vision for the Future</strong><br>With innovation, dedication, and a strong marketing network, we aspire to become a leading and trusted name in the real estate sector, delivering quality developments such as our prestigious Janki Villa Project, and creating opportunities for both our associates and our valued customers.</p>
        <p>We believe that marketing is not just about selling property—it is about building relationships, understanding people’s needs, and helping them make one of the most important decisions of their lives. Through your professionalism, enthusiasm, and commitment, we can strengthen our reputation and continue to grow as a trusted name in the real estate sector.</p>
        <p>Let us work together with enthusiasm, integrity, and determination to create new opportunities, achieve higher milestones, and contribute to the development of modern and affordable living spaces for our communities.</p>
        <p>Once again, I warmly welcome you to the team and wish you great success in your journey with us.</p>
        <p>With Best Wishes,</p><br>
        <p><strong>Amitabh Builders and Developers Pvt. Ltd.</strong></p>
        ';
    }
    private function getDefaultCustomerTemplate()
    {
        return '
        <div style="text-align: right; margin-bottom: 20px;">
            <strong>Date:</strong> [DATE]
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="color: #1A365D; border-bottom: 2px solid #D69E2E; display: inline-block; padding-bottom: 5px;">WELCOME LETTER</h2>
        </div>

        <p><strong>To,</strong><br>
        Mr./Mrs./Ms.: [CUSTOMER_NAME]<br>
        Father’s / Husband’s Name: [FATHER_NAME]<br>
        Address: [ADDRESS]</p>
        
        <p><strong>Customer Identification & Property Details</strong></p>
        
        <p>Customer Identification Number / Pass Book Number: [CUSTOMER_ID]</p>
        
        <p><strong>Dear Sir / Madam,</strong></p>
        
        <p>Warm greetings from Amitabh Builders And Developers Pvt. Ltd.</p>
        
        <p>We are extremely pleased that you have chosen to place your trust in our company for your property/investment. We sincerely thank you for deciding to associate with us. Your trust marks the beginning of a long-term relationship built on transparency, confidence, and mutual cooperation.</p>
        
        <p>Our objective is to provide reliable, well-planned, and affordable real estate opportunities so that every individual and family can achieve the dream of owning land or a home. In all our operations, we prioritize the highest standards of transparency, legal compliance, and customer satisfaction.</p>
        
        <p>Your association with us is very important. Our team will always be ready to assist and support you in all property-related processes such as documentation, guidance, and other formalities.</p>
        
        <p>We firmly believe that every customer is an important part of our growing family. Your trust motivates us to continuously develop better projects and deliver excellent services.</p>
        
        <p>Once again, we warmly welcome you to the Amitabh Builders And Developers Pvt. Ltd. family and wish you a bright future, prosperity, and continued success.</p>
        
        <p>Sincerely,</p>
        <br>
        <p><strong>Amitabh Builders And Developers Pvt. Ltd.</strong></p>
        <br><br>
        <p>Authorized Signatory<br>
        Name: _______________________<br>
        Designation: _______________________</p>
        ';
    }
}
