@extends('layout.app')

@section('content')
    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col-md-5">
                <h4 class="mb-0"><i class="fas fa-building text-brand-primary"></i> Property Types</h4>
            </div>
            <div class="col-md-7 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                <!-- Action Buttons -->
                <button class="btn btn-success secured-item" data-permission="p_type_export" id="exportBtn">
                    <i class="fas fa-file-excel"></i> Export
                </button>
                <button class="btn btn-info secured-item text-white" data-permission="p_type_print" id="printBtn">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-primary secured-item" data-permission="p_type_add_direct"
                    onclick="openModal('direct')">
                    <i class="fas fa-plus"></i> Add
                </button>
                <button class="btn btn-warning secured-item" data-permission="p_type_add_request"
                    onclick="openModal('request')">
                    <i class="fas fa-paper-plane"></i> Request
                </button>
                <button class="btn btn-danger secured-item" data-permission="p_type_delete" id="bulkDeleteBtn"
                    style="display:none;">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>
        </div>

        <!-- Desktop Datatable View -->
        <div class="card shadow-sm border-0 d-none d-md-block">
            <div class="card-body">
                <table class="table table-hover table-bordered w-100" id="propertyTypesTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%"><input type="checkbox" id="selectAll"></th>
                            <th>Type Name</th>
                            <th>Phase (Branch)</th>
                            <th>Status</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- 📱 NAYA: Mobile Cards Container -->
    <div class="d-md-none mt-3" id="mobileCardsContainer">
        <!-- Cards will be injected here automatically by DataTables -->
    </div>

    <!-- 📱 NAYA: Mobile Floating Action Button for Bulk Delete -->
    <div class="position-fixed bottom-0 start-50 translate-middle-x mb-5 pb-4 z-3" id="mobileFloatingAction" style="display: none; width: max-content;">
        <button class="btn btn-danger rounded-pill shadow-lg secured-item px-4" data-permission="p_type_delete" id="mobileBulkDeleteBtn">
            <i class="fas fa-trash me-2"></i> Delete Selected (<span id="mobileSelectedCount">0</span>)
        </button>
    </div>
    </div>

    <!-- Modal for Add/Edit -->
    <!-- Modal for Add/Edit -->
<div class="modal fade" id="propertyTypeModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="propertyTypeForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Property Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <!-- God Mode / Director Context Scope -->
                    <div id="scopeContainer">
                        <div class="mb-3 secured-item" data-permission="public" id="companyWrapper" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Select Company</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#company_id')">Select All</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#company_id')">Clear All</button>
                                </div>
                            </div>
                            <!-- 'multiple' added for array selection -->
                            <select class="form-control select2-modal" id="company_id" name="company_id[]" multiple="multiple" style="width:100%;"></select>
                        </div>

                        <div class="mb-3 secured-item" data-permission="public" id="branchWrapper" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Select Branch</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#branch_id')">Select All</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#branch_id')">Clear All</button>
                                </div>
                            </div>
                            <select class="form-control select2-modal" id="branch_id" name="branch_id[]" multiple="multiple" style="width:100%;"></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Select Phase <span class="text-danger">*</span></label>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#phase_id')">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#phase_id')">Clear All</button>
                            </div>
                        </div>
                        <select class="form-control select2-modal" id="phase_id" name="phase_id[]" multiple="multiple" required style="width:100%;"></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Property Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="type_name" name="type_name" placeholder="e.g., Residential, Commercial" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

 <script>
    let table;
    let globalContext = {}; 
    let allPhasesData = []; // Pure phase data ko store karne ke liye taaki bar-bar API na hit karni pade

    $(document).ready(function() {
        fetchContextAndSetup();

        // Fix: allowClear true kiya hai taaki 'x' mark aaye hataane ke liye
        $('.select2-modal').select2({
            dropdownParent: $('#propertyTypeModal'),
            placeholder: "Search and Select...",
            allowClear: true,
            width: '100%'
        });

        // Initialize DataTable (Same as before)
        table = $('#propertyTypesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: '/api/v1/property-types', type: 'GET' },
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: function(data) { return `<input type="checkbox" class="row-checkbox" value="${data}">`; }
                },
                { data: 'type_name' },
                { 
                    data: 'phase',
                    render: function(data, type, row) {
                        let phaseName = data ? data.phase_name : 'N/A';
                        let branchName = row.branch ? row.branch.branch_name : 'HO';
                        return `${phaseName} <small class="text-muted">(${branchName})</small>`;
                    }
                },
                {
                    data: 'status',
                    render: function(data) {
                        let badge = data === 'active' ? 'bg-success' : (data === 'pending' ? 'bg-warning' : 'bg-danger');
                        return `<span class="badge ${badge}">${data.toUpperCase()}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        let buttons = '';
                        let isGod = window.userGodMode || false; 
                        let perms = window.userPerms || [];

                        if (row.status === 'pending') {
                            if (isGod || perms.includes('p_type_approve') || perms.includes('p_type_appr')) {
                                buttons += `<button type="button" class="btn btn-sm btn-success me-1" onclick="actionApprove(${row.id})" title="Approve"><i class="fas fa-check"></i></button>`;
                            }
                            if (isGod || perms.includes('p_type_reject') || perms.includes('p_type_rej')) {
                                buttons += `<button type="button" class="btn btn-sm btn-danger me-1" onclick="actionReject(${row.id})" title="Reject"><i class="fas fa-times"></i></button>`;
                            }
                        }
                        
                        // DataTables 'columns' ke 'render' me AUR 'drawCallback' wale mobile cards me isko replace karo:

let rowDataStr = encodeURIComponent(JSON.stringify(row));
if (isGod || perms.includes('p_type_edit')) {
    buttons += `<button type="button" class="btn btn-sm btn-info me-1 text-white" onclick="editRow(this)" data-row="${rowDataStr}" title="Edit"><i class="fas fa-edit"></i></button>`;
}
                        return buttons;
                    }
                }
            ],
           drawCallback: function(settings) {
                // Default secured items hide/show
                if(typeof window.applyPermissions === 'function') {
                    window.applyPermissions(); 
                }

                // 📱 MOBILE CARD RENDERING LOGIC
                let api = this.api();
                let records = api.rows({page: 'current'}).data();
                let mobileHtml = '';

                if (records.length === 0) {
                    mobileHtml = `<div class="alert alert-secondary text-center shadow-sm">No Property Types found.</div>`;
                } else {
                    records.each(function(row) {
                        let phaseName = row.phase ? row.phase.phase_name : 'N/A';
                        let branchName = row.branch ? row.branch.branch_name : 'HO';
                        let badge = row.status === 'active' ? 'bg-success' : (row.status === 'pending' ? 'bg-warning' : 'bg-danger');
                        
                        let isGod = window.userGodMode || false; 
                        let perms = window.userPerms || [];
                        let buttons = '';

                        if (row.status === 'pending') {
                            if (isGod || perms.includes('p_type_approve') || perms.includes('p_type_appr')) {
                                buttons += `<button type="button" class="btn btn-sm btn-success me-1" onclick="actionApprove(${row.id})" title="Approve"><i class="fas fa-check"></i></button>`;
                            }
                            if (isGod || perms.includes('p_type_reject') || perms.includes('p_type_rej')) {
                                buttons += `<button type="button" class="btn btn-sm btn-danger me-1" onclick="actionReject(${row.id})" title="Reject"><i class="fas fa-times"></i></button>`;
                            }
                        }
                        
                        // DataTables 'columns' ke 'render' me AUR 'drawCallback' wale mobile cards me isko replace karo:

let rowDataStr = encodeURIComponent(JSON.stringify(row));
if (isGod || perms.includes('p_type_edit')) {
    buttons += `<button type="button" class="btn btn-sm btn-info me-1 text-white" onclick="editRow(this)" data-row="${rowDataStr}" title="Edit"><i class="fas fa-edit"></i></button>`;
}

                        // Build individual mobile card
                        mobileHtml += `
                        <div class="card shadow-sm border-0 mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="checkbox" class="mobile-row-checkbox form-check-input mt-0" value="${row.id}" style="width: 1.2rem; height: 1.2rem;">
                                        <h6 class="mb-0 fw-bold">${row.type_name}</h6>
                                    </div>
                                    <span class="badge ${badge}">${row.status.toUpperCase()}</span>
                                </div>
                                <div class="text-muted small mb-3">
                                    <i class="fas fa-building me-1 text-brand-primary"></i> ${phaseName} (${branchName})
                                </div>
                                <div class="d-flex justify-content-end border-top pt-2">
                                    ${buttons}
                                </div>
                            </div>
                        </div>`;
                    });
                }
                
                $('#mobileCardsContainer').html(mobileHtml);
                
                // Re-apply permissions for newly injected mobile buttons
                if(typeof window.applyPermissions === 'function') {
                    window.applyPermissions();
                }
            }
        });

        // 🟢 CASCADING LOGIC (Handles Array Values from Multi-Select)
        $('#company_id').on('change', function() {
            let compIds = $(this).val() || [];
            $('#branch_id').empty().trigger('change');
            $('#phase_id').empty().trigger('change');
            
            if(compIds.length > 0) {
                loadBranches(compIds);
            }
        });

        $('#branch_id').on('change', function() {
            let compIds = $('#company_id').val() || (globalContext.company_id ? [globalContext.company_id.toString()] : []);
            let branchIds = $(this).val() || (globalContext.branch_id ? [globalContext.branch_id.toString()] : []);
            
            filterAndLoadPhases(compIds, branchIds);
        });

     // 🟢 DESKTOP & MOBILE BULK DELETE LOGIC
        $('#selectAll').on('change', function() {
            $('.row-checkbox').prop('checked', $(this).prop('checked'));
            toggleBulkDeleteBtn();
        });

        // Desktop checkboxes
        $('#propertyTypesTable').on('change', '.row-checkbox', function() { 
            toggleBulkDeleteBtn(); 
        });

        // Mobile checkboxes
        $(document).on('change', '.mobile-row-checkbox', function() {
            let count = $('.mobile-row-checkbox:checked').length;
            if(count > 0) {
                $('#mobileSelectedCount').text(count);
                $('#mobileFloatingAction').fadeIn();
            } else {
                $('#mobileFloatingAction').fadeOut();
            }
        });

        // Combine Desktop & Mobile Delete Triggers
        $('#bulkDeleteBtn, #mobileBulkDeleteBtn').on('click', function() {
            let ids = [];
            
            // Check which view is active and collect IDs
            if ($(window).width() >= 768) {
                $('.row-checkbox:checked').each(function() { ids.push($(this).val()); });
            } else {
                $('.mobile-row-checkbox:checked').each(function() { ids.push($(this).val()); });
            }

            if (ids.length === 0) return;

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#E53E3E',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('/api/v1/property-types/bulk-delete', { ids: ids }, function(res) {
                        Swal.fire('Deleted!', res.message, 'success');
                        table.ajax.reload(null, false);
                        $('#selectAll').prop('checked', false);
                        toggleBulkDeleteBtn();
                        $('#mobileFloatingAction').fadeOut();
                    });
                }
            });
        });

        $('#propertyTypeForm').on('submit', function(e) {
            e.preventDefault();
            let id = $('#edit_id').val();
            let url = id ? `/api/v1/property-types/${id}` : '/api/v1/property-types';
            let method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: method,
                data: $(this).serialize(),
                success: function(res) {
                    $('#propertyTypeModal').modal('hide');
                    Swal.fire('Success', res.message, 'success');
                    table.ajax.reload(null, false);
                },
                error: function() { Swal.fire('Error', 'Something went wrong!', 'error'); }
            });
        });
    });

    // 🟢 UTILITY FUNCTIONS FOR SELECT ALL / CLEAR ALL
    window.selectAll = function(selector) {
        $(selector + ' > option').prop("selected", "selected");
        $(selector).trigger("change");
    };

    window.clearAll = function(selector) {
        $(selector).val(null).trigger("change");
    };

    function toggleBulkDeleteBtn() {
        if ($('.row-checkbox:checked').length > 0) $('#bulkDeleteBtn').show();
        else $('#bulkDeleteBtn').hide();
    }

    function fetchContextAndSetup() {
        $.get('/api/v1/context', function(res) {
            globalContext = res;
            if (res.is_god) {
                $('#companyWrapper').show();
                $('#branchWrapper').show();
                loadCompanies();
            } else if (res.is_director) {
                $('#branchWrapper').show();
                loadBranches([res.company_id]);
            } else {
                // Fetch phases initially so data is ready
                cacheAllPhases(res.company_id, res.branch_id);
            }
        });
    }

    function loadCompanies() {
        $.get('/api/v1/get-active-companies', function(res) {
            let options = '';
            if(res.data) {
                res.data.forEach(c => { options += `<option value="${c.id}">${c.company_name}</option>`; });
            }
            $('#company_id').html(options).trigger('change');
        });
    }

  function loadBranches(companyIdsArray) {
        let options = '';
        
        // 1. Har selected company ka naam nikal kar uska specific Head Office (HO) option banao
        $('#company_id option:selected').each(function() {
            let compId = $(this).val();
            let compName = $(this).text();
            if (compId) {
                // Value ko 'HO_compId' rakha hai taaki phase ko filter karne me exact pata chale
                options += `<option value="HO_${compId}">Head Office (${compName})</option>`;
            }
        });

        // Agar array empty hai to sirf options (jo khali honge ya HO honge) update karo aur phases clear karo
        if (!companyIdsArray || companyIdsArray.length === 0) {
            $('#branch_id').html(options).trigger('change');
            cacheAllPhases([]);
            return;
        }

        // 2. Baki actual branches API se fetch karo (Promise.all fix ke sath)
        let requests = companyIdsArray.map(id => {
            return new Promise((resolve) => {
                $.get(`/api/v1/phases/get-branches/${id}`, function(res) {
                    resolve(res.data || []);
                }).fail(function() {
                    resolve([]); 
                });
            });
        });
        
        Promise.all(requests).then(function(results) {
            results.forEach(branchArray => {
                branchArray.forEach(b => {
                    options += `<option value="${b.id}">${b.branch_name}</option>`;
                });
            });
            
            // UI me load karo
            $('#branch_id').html(options).trigger('change');
            cacheAllPhases(companyIdsArray);
        });
    }
    function cacheAllPhases(companyIdsArray) {
        // Fetch all phases once, then we filter them strictly in JS
        $.get('/api/v1/phases', function(res) {
            if(res.success && res.data) {
                allPhasesData = res.data; 
                let branchIds = $('#branch_id').val() || [];
                filterAndLoadPhases(companyIdsArray, branchIds);
            }
        });
    }

   function filterAndLoadPhases(companyIdsArray, branchIdsArray) {
        let options = '';
        
        allPhasesData.forEach(p => {
            let compIdStr = p.company_id ? p.company_id.toString() : '';
            // Backend me HO ka phase_id ya branch_id null hota hai
            let phaseBranchIdStr = p.branch_id ? p.branch_id.toString() : ''; 
            
            // 1. Company Match Check
            let isCompanyMatch = companyIdsArray.length === 0 || companyIdsArray.includes(compIdStr);
            
            // 2. Strict Branch/HO Match Check
            let isBranchMatch = false;
            
            if (branchIdsArray.length === 0) {
                isBranchMatch = true; // Agar branch me koi check nahi lagaya to sab valid hai
            } else {
                if (phaseBranchIdStr === "") {
                    // Agar ye phase HO ka hai, to check karo ki selection me is company ka HO selected hai ya nahi (e.g. "HO_1")
                    if (branchIdsArray.includes("HO_" + compIdStr)) {
                        isBranchMatch = true;
                    }
                } else {
                    // Agar phase kisi normal branch ka hai
                    if (branchIdsArray.includes(phaseBranchIdStr)) {
                        isBranchMatch = true;
                    }
                }
            }

            // Dono conditions meet hone par UI me add karo
            if(isCompanyMatch && isBranchMatch) {
                let bName = p.branch ? p.branch.branch_name : 'HO';
                options += `<option value="${p.id}">${p.phase_name} (${bName})</option>`;
            }
        });

        $('#phase_id').html(options).trigger('change');
    }


    // Modal Operations
    window.openModal = function(type) {
        $('#propertyTypeForm')[0].reset();
        $('#edit_id').val('');
        $('#company_id, #branch_id, #phase_id').val(null).trigger('change');
        $('#modalTitle').text(type === 'direct' ? 'Add Property Type' : 'Request Property Type');
        $('#saveBtn').text(type === 'direct' ? 'Save' : 'Submit Request');
        $('#propertyTypeModal').modal('show');
    };

    window.editRow = function(btn) {
        // Button se data nikal kar wapas object banaya
        let row = JSON.parse(decodeURIComponent($(btn).data('row')));
        
        $('#propertyTypeForm')[0].reset();
        $('#edit_id').val(row.id);
        $('#type_name').val(row.type_name);

        // Naming aur IDs extract ki
        let compId = row.company_id;
        let compName = row.company ? row.company.company_name : 'Selected Company';
        
        let branchId = row.branch_id;
        let branchName = row.branch ? row.branch.branch_name : `Head Office (${compName})`;
        let branchVal = branchId ? branchId : `HO_${compId}`; // HO handling

        let phaseId = row.phase_id;
        let phaseName = row.phase ? row.phase.phase_name : 'Selected Phase';
        let phaseLabel = `${phaseName} (${branchId ? branchName : 'HO'})`;

        // 1. Company Pre-fill
        if($('#company_id option[value="'+compId+'"]').length === 0) {
            $('#company_id').append(new Option(compName, compId, true, true));
        }
        $('#company_id').val([compId]).trigger('change');

        // 2. Branch Pre-fill (Thoda delay denge taaki 'change' trigger complete ho jaye)
        setTimeout(() => {
            if($('#branch_id option[value="'+branchVal+'"]').length === 0) {
                $('#branch_id').append(new Option(branchName, branchVal, true, true));
            }
            $('#branch_id').val([branchVal]).trigger('change');
            
            // 3. Phase Pre-fill
            setTimeout(() => {
                if($('#phase_id option[value="'+phaseId+'"]').length === 0) {
                    $('#phase_id').append(new Option(phaseLabel, phaseId, true, true));
                }
                $('#phase_id').val([phaseId]).trigger('change');
            }, 300);
            
        }, 300);

        $('#modalTitle').text('Edit Property Type');
        $('#saveBtn').text('Update');
        $('#propertyTypeModal').modal('show');
    };

  // Export & Print Real Actions with Token
        $('#exportBtn').on('click', function() {
            let portal = window.location.pathname.split('/')[1]; 
            // LocalStorage se specific portal ka ya common token nikalo
            let token = localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || localStorage.getItem('token') || '';
            
            window.location.href = `/${portal}/property-types/export?token=${token}`; 
        });

        $('#printBtn').on('click', function() {
            let portal = window.location.pathname.split('/')[1];
            let token = localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || localStorage.getItem('token') || '';
            
            window.open(`/${portal}/property-types/print?token=${token}`, '_blank');
        });
    window.actionApprove = function(id) { /* Previous Code */ };
    window.actionReject = function(id) { /* Previous Code */ };
</script>
@endpush