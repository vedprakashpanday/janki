@php
    // 🔥 Strict Array Check for Attachments
    $hasAttachments =
        !empty($app->proof_attachments) &&
        is_array($app->proof_attachments) &&
        count(array_filter($app->proof_attachments)) > 0;
@endphp

<div
    style="position: relative; padding: 5px; background: #fff; min-height: 500px; color: #000; font-family: Arial, sans-serif;">

    @if ($app->status === 'rejected')
        <div
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); color: rgba(220, 53, 69, 0.15); font-size: 80px; font-weight: 900; border: 8px solid rgba(220, 53, 69, 0.15); padding: 10px 30px; border-radius: 15px; pointer-events: none; z-index: 0; letter-spacing: 10px;">
            REJECTED
        </div>
    @elseif ($app->status === 'approved' || $app->status === 'active')
        <div
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); color: rgba(40, 167, 69, 0.15); font-size: 80px; font-weight: 900; border: 8px solid rgba(40, 167, 69, 0.15); padding: 10px 30px; border-radius: 15px; pointer-events: none; z-index: 0; letter-spacing: 10px;">
            APPROVED
        </div>
    @elseif ($company && !empty($company->company_logo))
        <img src="{{ asset($company->company_logo) }}"
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 350px; opacity: 0.08; pointer-events: none; z-index: 0;"
            alt="Watermark">
    @endif

    <div style="position: relative; z-index: 1;">
        <x-print-header :company="$company" :branch="$branch" />

        <h3
            style="text-align: center; font-weight: bold; text-decoration: underline; margin-top: 20px; margin-bottom: 20px; text-transform: uppercase;">
            {{ $app->application_type === 'Short Leave' ? 'SHORT LEAVE APPLICATION FORM' : $app->application_type . ' APPLICATION FORM' }}
        </h3>

       @php
            // 🔥 FIX: Added member_name check to remove "N/A" bug
            $name = $app->user_type === 'employee' ? ($app->employee->full_name ?? 'N/A') : ($app->member->member_name ?? $app->member->full_name ?? 'N/A');
            $empCode = $app->user_type === 'employee' ? ($app->employee->member_id ?? ($app->employee->id ?? 'N/A')) : ($app->member->member_id ?? 'N/A');
            $desig = $app->designation->designation_name ?? 'N/A';
            $appDate = \Carbon\Carbon::parse($app->created_at)->format('d/m/Y h:i A');

            // 🔥 FIX: Dynamic Labels Based on User Type
            $codeLabel = $app->user_type === 'employee' ? 'EMP. CODE:' : 'MEMBER CODE:';
            $nameLabel = $app->user_type === 'employee' ? 'EMP. NAME:' : 'MEMBER NAME:';
            $sigLabel  = $app->user_type === 'employee' ? 'SIGNATURE OF EMPLOYEE' : 'SIGNATURE OF MEMBER';

            $toRole = 'The Management';
            $toCompany = $company ? $company->company_name : 'COMPANY NAME';

            if (str_contains($app->applied_to, 'CEO') || str_contains($app->applied_to, 'Master Admin')) {
                $toRole = 'The CEO';
                $toCompany = 'AMITABH BUILDERS & DEVELOPERS PVT. LTD.';
            } elseif (str_contains($app->applied_to, 'Director')) {
                $toRole = 'The Director';
                $toCompany = $company ? $company->company_name : 'COMPANY NAME';
            }
        @endphp

        <div style="font-size: 14px; margin-bottom: 15px; line-height: 1.5;">
            <strong>To,</strong><br>
            <strong>{{ $toRole }},</strong><br>
            {{ $toCompany }}
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;" border="1">
            <tr>
                <td style="padding: 8px; width: 50%;"><strong>{{ $codeLabel }}</strong> {{ $empCode }}</td>
                <td style="padding: 8px; width: 50%;"><strong>DATE:</strong> {{ $appDate }}</td>
            </tr>
            <tr>
                <td style="padding: 8px;"><strong>{{ $nameLabel }}</strong> {{ strtoupper($name) }}</td>
                <td style="padding: 8px;"><strong>DESIGNATION:</strong> {{ strtoupper($desig) }}</td>
            </tr>
        </table>

        <p style="font-size: 14px; font-weight: bold;">
            Dear Sir/Mam,
            @if ($app->is_paid_leave)
                <span
                    style="color: green; border: 1px solid green; padding: 2px 5px; border-radius: 4px; float:right; font-size: 12px;">[
                    PAID LEAVE REQUEST ]</span>
            @endif
        </p>

        @php
            $finalStart = $app->approved_start_datetime ?? $app->start_datetime;
            $finalEnd = $app->approved_end_datetime ?? $app->end_datetime;
            $finalDuration = $app->approved_duration ?? $app->duration;
            $finalResume = $app->approved_resume_datetime ?? $app->resume_datetime;

            $resumeTime = $finalResume ? \Carbon\Carbon::parse($finalResume)->format('d/m/Y h:i A') : '__________';
            $emergContact = $app->emergency_contact ?: '__________';
            $emergEmail = $app->emergency_email ?: '__________';

            $leaveTypeTxt = $app->application_type === 'Short Leave' ? 'Hours of short leave' : 'days of leave';
            $paidTxt = $app->is_paid_leave ? ' as a <strong>Paid Leave</strong>' : '';
        @endphp

        @if ($app->application_type === 'Short Leave')
            @php
                $startTime = \Carbon\Carbon::parse($finalStart)->format('h:i A');
                $endTime = \Carbon\Carbon::parse($finalEnd)->format('h:i A');
            @endphp
            <p style="font-size: 14px; line-height: 1.6; text-align: justify;">
                I hereby request your kind approval for <strong>{{ $finalDuration }} {{ $leaveTypeTxt }}</strong>, from
                <strong>{{ $startTime }}</strong> to <strong>{{ $endTime }}</strong>{{ $paidTxt }}, due
                to the reason mentioned below. I assure you that I have reviewed my work responsibilities and will
                ensure smooth coordination during my absence. I will resume duty on
                <strong>{{ $resumeTime }}</strong> and will remain available on my mobile number
                <strong>{{ $emergContact }}</strong> and email <strong>{{ $emergEmail }}</strong> in case of urgent
                communication.
            </p>
        @else
            @php
                $startDate = \Carbon\Carbon::parse($finalStart)->format('d/m/Y');
                $endDate = \Carbon\Carbon::parse($finalEnd)->format('d/m/Y');
            @endphp
            <p style="font-size: 14px; line-height: 1.6; text-align: justify;">
                I hereby request your kind approval for <strong>{{ $finalDuration }} {{ $leaveTypeTxt }}</strong>,
                from <strong>{{ $startDate }}</strong> to <strong>{{ $endDate }}</strong>{{ $paidTxt }},
                due to the reason mentioned below. I assure you that I have reviewed my work responsibilities and will
                ensure smooth coordination during my absence. I will resume duty on
                <strong>{{ $resumeTime }}</strong> and will remain available on my mobile number
                <strong>{{ $emergContact }}</strong> and email <strong>{{ $emergEmail }}</strong> in case of urgent
                communication.
            </p>
        @endif

        <div style="margin-top: 20px; font-size: 14px;">
            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">Reason for
                {{ $app->application_type }} & Justification</p>
            <div
                style="padding: 10px; border: 1px solid #000; min-height: 60px; white-space: pre-wrap; margin-bottom: 15px; margin-top: 5px;">
                {{ $app->reason }}</div>

            <p style="margin-bottom: 5px;">I further confirm that:</p>
            <ul style="line-height: 1.6; padding-left: 20px; margin-bottom: 15px;">
                <li>I have handed over all necessary information/tasks to my colleague/team to avoid disruption of work.
                </li>
                <li>I will be available on my mobile phone for any urgent queries or instructions.</li>
                <li>I will rejoin duty on the specified date without fail and will continue my responsibilities with
                    full dedication.</li>
            </ul>
@if (!empty($app->remarks))
                @php
                    $isRejected = $app->status === 'rejected';
                    $remarkLabel = $isRejected ? "Rejecter's Remark" : "Approver's Remark";
                    $borderColor = $isRejected ? '#dc3545' : '#28a745';
                @endphp

                <div
                    style="padding: 10px; background-color: #f8f9fa; border-left: 4px solid {{ $borderColor }}; margin-bottom: 15px;">
                    <!-- 🔥 FIX: Label strictly Red aur actual Remark strictly Green -->
                    <strong style="color: #dc3545;">{{ $remarkLabel }}:</strong>
                    <span style="color: #28a745; font-weight: 600;">{{ $app->remarks }}</span>
                </div>
            @endif

            @if ($hasAttachments)
                <div id="attachment-label-box"
                    style="padding: 8px; border: 1px dashed #17a2b8; display: inline-block; font-size: 13px; color: #17a2b8; font-weight: bold; margin-bottom: 25px;">
                    <i class="fas fa-paperclip"></i> Attachment Available (See Below/Portal)
                </div>
            @else
                <div style="margin-bottom: 25px;"></div>
            @endif
        </div>

       @php
            // 🔥 NAYA: ACCURATE SIGNATURE & DATE LOGIC
            $approverText = 'PENDING APPROVAL';
            $signer = null;
            $actionDate = null;
            $textColor = '#000'; // Default color

            // Determine Signer based on exact status
            if ($app->status === 'approved' || $app->status === 'active') {
                $signer = $app->approver;
                $actionDate = $app->updated_at;
                $textColor = '#28a745'; // Green for approved
            } elseif ($app->status === 'rejected') {
                $signer = $app->rejecter;
                $actionDate = $app->updated_at;
                $textColor = '#dc3545'; // Red for rejected
            }

            if ($signer) {
                // Get the exact name of the person who acted
                $sName = strtoupper($signer->full_name ?? $signer->name ?? 'AUTHORISED SIGNATORY');
                
                if (str_contains($app->applied_to, 'CEO') && isset($signer->ceo_id)) {
                    $approverText = "<span style='color: {$textColor};'>{$sName} ({$signer->ceo_id})</span>";
                } elseif (str_contains($app->applied_to, 'Director') && isset($signer->director_id)) {
                    $approverText = "<span style='color: {$textColor};'>{$sName} ({$signer->director_id})</span>";
                } else {
                    // 🔥 Agar CEO ya Director nahi hai, toh seedha HR MANAGEMENT aayega
                    $approverText = "<span style='color: {$textColor};'>HR MANAGEMENT</span>";
                }
            } else {
                // Fallback safe checking
                if ($app->status === 'rejected') {
                    $approverText = "<span style='color: #dc3545;'>REJECTED</span>";
                } elseif ($app->status === 'approved' || $app->status === 'active') {
                    $approverText = "<span style='color: #28a745;'>HR MANAGEMENT</span>";
                } else {
                    $approverText = "<span style='color: #ffc107;'>PENDING APPROVAL</span>";
                }
            }
        @endphp

        <div style="display: flex; justify-content: space-between; margin-top: 50px; text-align: center;">
            <div style="width: 45%;">
                <div style="font-weight: bold; margin-bottom: 5px; min-height: 40px; display: flex; align-items: flex-end; justify-content: center; text-transform: uppercase;">
                    {{ $name }} ({{ $empCode }})
                </div>
                <div style="border-top: 1px dashed #000; font-size: 14px; font-weight: bold; padding-top: 5px;">
                    {{ $sigLabel }}
                </div>
            </div>
            
            <div style="width: 45%;">
                <div style="font-weight: bold; margin-bottom: 5px; min-height: 40px; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; text-transform: uppercase;">
                    <div>{!! $approverText !!}</div>
                    
                    @if($actionDate)
                        <div style="font-size: 12px; font-weight: bold; margin-top: 3px; color: {{ $textColor }}; text-transform: none;">
                            Date: {{ \Carbon\Carbon::parse($actionDate)->format('d/m/Y h:i A') }}
                        </div>
                    @endif
                </div>
                <div style="border-top: 1px dashed #000; font-size: 14px; font-weight: bold; padding-top: 5px;">
                    SIGNATURE & DATE<br>(AUTHORISED SIGNATORY)
                </div>
            </div>
        </div>
    </div>
</div>
@if ($hasAttachments)
    <div id="print-attachments-section" class="no-print"
        style="position: relative; padding: 5px; background: #fff; min-height: 500px; color: #000; font-family: Arial, sans-serif; margin-top: 40px; page-break-before: always;">

        @if ($app->status === 'rejected')
            <div
                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); color: rgba(220, 53, 69, 0.15); font-size: 80px; font-weight: 900; border: 8px solid rgba(220, 53, 69, 0.15); padding: 10px 30px; border-radius: 15px; pointer-events: none; z-index: 0; letter-spacing: 10px;">
                REJECTED
            </div>
        @elseif ($app->status === 'approved' || $app->status === 'active')
            <div
                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); color: rgba(40, 167, 69, 0.15); font-size: 80px; font-weight: 900; border: 8px solid rgba(40, 167, 69, 0.15); padding: 10px 30px; border-radius: 15px; pointer-events: none; z-index: 0; letter-spacing: 10px;">
                APPROVED
            </div>
        @elseif ($company && !empty($company->company_logo))
            <img src="{{ asset($company->company_logo) }}"
                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 350px; opacity: 0.08; pointer-events: none; z-index: 0;"
                alt="Watermark">
        @endif

        <div style="position: relative; z-index: 1;">
            <div
                style="border: 1px solid #000; padding: 10px; text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 20px; text-transform: uppercase;">
                ATTACHED PROOF DOCUMENT(S)
            </div>

            <div style="display: flex; flex-direction: column; gap: 30px; align-items: center; width: 100%;">
                @foreach ($app->proof_attachments as $proof)
                    @php
                        $ext = strtolower(pathinfo($proof, PATHINFO_EXTENSION));
                        $url = asset($proof);
                    @endphp

                    @if (in_array($ext, ['jpg', 'jpeg', 'png']))
                        <div
                            style="width: 100%; text-align: center; border: 1px solid #ccc; padding: 5px; background: #fff;">
                            <img src="{{ $url }}"
                                style="max-width: 100%; max-height: 900px; object-fit: contain;">
                        </div>
                    @else
                        <div
                            style="width: 100%; border: 1px solid #ccc; padding: 30px; text-align: center; background: #f8f9fa;">
                            <i class="fas fa-file-pdf"
                                style="font-size: 50px; color: #dc3545; margin-bottom: 15px;"></i>
                            <h5 style="margin: 0; color: #333;">PDF Document Attached</h5>
                            <p style="margin-top: 10px; font-size: 14px;"><a href="{{ $url }}" target="_blank"
                                    style="color: #0d6efd; text-decoration: underline;">Click here to View / Download
                                    PDF</a></p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endif
