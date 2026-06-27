 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div style="position: relative; padding: 10px; background: #fff; min-height: 500px;">
    
    @if($ta->status === 'rejected')
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); color: rgba(220, 53, 69, 0.15); font-size: 80px; font-weight: 900; border: 8px solid rgba(220, 53, 69, 0.15); padding: 10px 30px; border-radius: 15px; pointer-events: none; z-index: 0; letter-spacing: 10px;">
            REJECTED
        </div>
    @elseif ($company && !empty($company->company_logo))
        <img src="{{ asset($company->company_logo) }}" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 350px; opacity: 0.08; pointer-events: none; z-index: 0;" alt="Watermark">
    @endif

    <div style="position: relative; z-index: 1;">
        <x-print-header :company="$company" :branch="$branch" />

        <div style="text-align: center; font-size: 16px; font-weight: bold; text-decoration: underline; margin: 15px 0 5px;">TRAVELLING EXPENSE BILL</div>
        <div style="text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 20px;">BILL SR. NO.: TA-{{ str_pad($ta->id, 5, '0', STR_PAD_LEFT) }}</div>

        <table class="table table-bordered table-sm" style="font-size: 13px; border-color: #000;">
            <tr>
                <td style="background: #f8f9fa; font-weight: bold; width: 25%;">Date</td>
                <td style="width: 25%;">{{ \Carbon\Carbon::parse($ta->ta_date)->format('d/m/Y') }}</td>
                <td style="background: #f8f9fa; font-weight: bold; width: 25%;">Vehicle No.</td>
                <td style="width: 25%;">{{ $ta->vehicle_no ?? '.......' }}</td>
            </tr>
            <tr>
                <td style="background: #f8f9fa; font-weight: bold;">Employee Name</td>
                <td style="font-weight: bold;">{{ $ta->employee->full_name ?? 'N/A' }}</td>
                <td style="background: #f8f9fa; font-weight: bold;">Employee Code</td>
                <td>{{ $ta->employee->member_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="background: #f8f9fa; font-weight: bold;">Person Name / Contact</td>
                <td>
                    <b>{{ $ta->person_name ?? 'Self' }}</b> 
                    @if($ta->person_number)
                        <br><small><i class="fas fa-phone-alt"></i> {{ $ta->person_number }}</small>
                    @endif
                </td>
                <td style="background: #f8f9fa; font-weight: bold;">No. of Persons</td>
                <td><span class="badge bg-secondary">x{{ $ta->number_of_persons ?? 1 }}</span></td>
            </tr>
            <tr>
                <td style="background: #f8f9fa; font-weight: bold;">Purpose of Work</td>
                <td colspan="3">{!! nl2br(e($ta->purpose ?? '.......')) !!}</td>
            </tr>
            <tr>
                <td style="background: #f8f9fa; font-weight: bold;">Destination</td>
                <td colspan="3">{!! nl2br(e($ta->destination ?? '.......')) !!}</td>
            </tr>
            <tr>
                <td style="background: #f8f9fa; font-weight: bold;">Distance (KMs)</td>
                <td>{{ $ta->distance_km ? $ta->distance_km . ' Km' : '.......' }}</td>
                <td style="background: #f8f9fa; font-weight: bold;">Fuel (Litre)</td>
                <td>{{ $ta->fuel_litre ? $ta->fuel_litre . ' Ltr' : '.......' }}</td>
            </tr>
            <tr>
                <td style="background: #f8f9fa; font-weight: bold;">In Time</td>
                <td>{{ $ta->in_time ?? '.......' }}</td>
                <td style="background: #f8f9fa; font-weight: bold;">Out Time</td>
                <td>{{ $ta->out_time ?? '.......' }}</td>
            </tr>
            <tr style="background: #f1f3f5;">
                <td style="font-weight: bold;">Total Amount</td>
                <td colspan="3" style="font-size: 15px; font-weight: bold; color: #1A365D;">₹{{ number_format($ta->amount, 2) }}</td>
            </tr>
            @if($ta->remarks)
            <tr>
                <td style="background: #fff3cd; font-weight: bold; color: #856404;">Remarks</td>
                <td colspan="3" style="font-style: italic; color: #856404; background: #fff3cd;">{{ $ta->remarks }}</td>
            </tr>
            @endif
        </table>

       <!-- 🔥 MULTIPLE PROOFS VIEWER (MODAL) 🔥 -->
        @php
            $proofs = [];
            if ($ta->proof_file) {
                $decoded = json_decode($ta->proof_file, true);
                $proofs = is_array($decoded) ? $decoded : [$ta->proof_file];
            }
        @endphp

        @if(count($proofs) > 0)
            <div class="mt-4 p-3" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px;">
                <h6 class="fw-bold mb-3 text-center text-decoration-underline" style="color: #1A365D;"><i class="fas fa-paperclip"></i> ATTACHED PROOF(S)</h6>
                <div class="row text-center">
                    @foreach($proofs as $proofPath)
                        <div class="col-6 mb-3">
                            @if(\Illuminate\Support\Str::endsWith(strtolower($proofPath), 'pdf'))
                                <iframe src="{{ asset($proofPath) }}" width="100%" height="250px" style="border: 1px solid #ccc; border-radius: 5px;"></iframe>
                                <a href="{{ asset($proofPath) }}" target="_blank" class="btn btn-sm btn-outline-danger mt-1"><i class="fas fa-file-pdf"></i> Open PDF</a>
                            @else
                                <a href="{{ asset($proofPath) }}" target="_blank">
                                    <img src="{{ asset($proofPath) }}" style="width: 100%; height: 250px; border-radius: 5px; border: 1px solid #ccc; object-fit: cover; background: #fff;">
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif


        <div class="d-flex justify-content-between mt-5 pt-4 text-center">
            <div style="width: 40%;">
                <div style="font-weight: bold; margin-bottom: 5px;">{{ $ta->employee->full_name ?? '' }} ({{ $ta->employee->member_id ?? '' }})</div>
                <div style="border-top: 1px dashed #000; font-size: 12px; font-weight: bold;">SIGNATURE OF EMPLOYEE</div>
            </div>
            <div style="width: 40%;">
                <div style="font-weight: bold; margin-bottom: 5px;">
                    @if($ta->status === 'active')
                        {{ $ta->approver ? $ta->approver->full_name . ' (' . $ta->approver->member_id . ')' : 'HR MANAGEMENT' }}
                    @elseif($ta->status === 'rejected')
                        <span class="text-danger">REJECTED</span>
                    @else
                        <span class="text-muted" style="font-style: italic; font-weight: normal;">Pending Approval</span>
                    @endif
                </div>
                <div style="border-top: 1px dashed #000; font-size: 12px; font-weight: bold;">SIGNATURE OF APPROVER</div>
            </div>
        </div>
    </div>
</div>