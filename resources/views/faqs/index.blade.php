@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        .table-custom th {
            background-color: var(--sidebar-bg, #0f172a);
            color: #fff;
            font-size: 13px;
            border: none;
            padding: 12px;
        }
        .table-custom td {
            font-size: 13px;
            vertical-align: middle;
            padding: 12px;
        }
        .pro-badge {
            background: linear-gradient(45deg, #d4af37, #ffd700);
            color: #000;
            font-weight: bold;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .raw-badge {
            background: #e2e8f0;
            color: #475569;
            font-weight: bold;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg, #0f172a);">
                <i class="fas fa-brain text-primary me-2"></i> FAQ & Bot Training
            </h4>
            <button class="btn btn-primary px-4 py-2 shadow-sm fw-bold" onclick="openFaqModal('add')">
                <i class="fas fa-plus me-2"></i> Add New FAQ
            </button>
        </div>

        <!-- Filter / Tabs -->
        <ul class="nav nav-tabs mb-4" id="faqTabs">
            <li class="nav-item">
                <button class="nav-link active fw-bold text-success" data-status="active" onclick="filterTable('active')">
                    <i class="fas fa-check-circle me-1"></i> Active FAQs
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold text-danger" data-status="unanswered" onclick="filterTable('unanswered')">
                    <i class="fas fa-question-circle me-1"></i> Unanswered (Bot Failed)
                </button>
            </li>
        </ul>

        <!-- Data Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 table-responsive">
                <table id="faqTable" class="table table-hover table-custom w-100">
                    <thead>
                        <tr>
                            <th width="10%">Category</th>
                            <th width="25%">Question / Keywords</th>
                            <th width="35%">Answer</th>
                            <th width="15%">AI Status</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit FAQ Modal -->
    <div class="modal fade" id="faqModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background-color: var(--sidebar-bg, #0f172a); color: white;">
                    <h5 class="modal-title fw-bold" id="modalTitle">Manage FAQ</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="faqForm" class="row g-3">
                        <input type="hidden" id="faq_id">
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Category</label>
                            <select id="f_category" class="form-select" required>
                                <option value="General">General</option>
                                <option value="Plots">Plots</option>
                                <option value="Villa">Villa</option>
                                <option value="Plots & Villa">Plots & Villa</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select id="f_status" class="form-select" required>
                                <option value="active">Active (Bot will use this)</option>
                                <option value="unanswered">Unanswered (Draft)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Keywords (Comma separated)</label>
                            <input type="text" id="f_keywords" class="form-control" placeholder="e.g. price, cost, kitne ka hai, rate">
                            <small class="text-muted">Bot will trigger this answer if user types these words.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Question</label>
                            <textarea id="f_question" class="form-control" rows="2" placeholder="Enter the exact question..." required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Answer (Raw or Pro)</label>
                            <textarea id="f_answer" class="form-control" rows="4" placeholder="Type your raw answer here. AI will optimize it later on first use."></textarea>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="f_is_pro_reply">
                                <label class="form-check-label fw-bold" for="f_is_pro_reply">Mark as AI Optimized (Pro Reply)</label>
                            </div>
                            <small class="text-muted">If unchecked, Gemini will format it automatically upon first user trigger.</small>
                        </div>

                        <div class="col-12 text-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-5 fw-bold" id="saveFaqBtn">Save FAQ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let faqTable;
        let currentStatus = 'active';

        $(document).ready(function() {
            // Initialize DataTable
            faqTable = $('#faqTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/faqs', // Aapka API Route
                    data: function(d) {
                        d.status = currentStatus;
                    }
                },
                columns: [
                    { data: 'category', name: 'category' },
                    { 
                        data: 'question', 
                        render: function(data, type, row) {
                            let kw = row.keywords ? `<br><small class="text-primary"><i class="fas fa-tags"></i> ${row.keywords}</small>` : '';
                            return `<strong>${data}</strong>${kw}`;
                        }
                    },
                    { data: 'answer', name: 'answer' },
                    { 
                        data: 'is_pro_reply', 
                        render: function(data) {
                            return data == 1 
                                ? `<span class="pro-badge"><i class="fas fa-magic"></i> AI Optimized</span>` 
                                : `<span class="raw-badge">Raw Entry</span>`;
                        }
                    },
                    { 
                        data: 'id', 
                        render: function(data) {
                            return `
                                <button class="btn btn-sm btn-light text-primary me-1" onclick="openFaqModal('edit', ${data})"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-light text-danger" onclick="deleteFaq(${data})"><i class="fas fa-trash"></i></button>
                            `;
                        }
                    }
                ]
            });

            // Handle Tab Clicks
            $('.nav-link').click(function() {
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
            });
        });

        function filterTable(status) {
            currentStatus = status;
            faqTable.ajax.reload();
        }

        window.openFaqModal = function(type, id = null) {
            $('#faqForm')[0].reset();
            $('#faq_id').val('');
            
            if(type === 'add') {
                $('#modalTitle').text('Add New FAQ');
                $('#saveFaqBtn').text('Save FAQ');
                $('#faqModal').modal('show');
            } else {
                $('#modalTitle').text('Edit FAQ');
                $('#saveFaqBtn').text('Update FAQ');
                
                // Fetch existing data
                $.get(`/api/v1/faqs/${id}`, function(res) {
                    $('#faq_id').val(res.data.id);
                    $('#f_category').val(res.data.category);
                    $('#f_status').val(res.data.status);
                    $('#f_keywords').val(res.data.keywords);
                    $('#f_question').val(res.data.question);
                    $('#f_answer').val(res.data.answer);
                    $('#f_is_pro_reply').prop('checked', res.data.is_pro_reply == 1);
                    $('#faqModal').modal('show');
                });
            }
        }

        $('#faqForm').submit(function(e) {
            e.preventDefault();
            let id = $('#faq_id').val();
            let url = id ? `/api/v1/faqs/${id}` : '/api/v1/faqs';
            let method = id ? 'PUT' : 'POST';
            
            let formData = {
                category: $('#f_category').val(),
                status: $('#f_status').val(),
                keywords: $('#f_keywords').val(),
                question: $('#f_question').val(),
                answer: $('#f_answer').val(),
                is_pro_reply: $('#f_is_pro_reply').is(':checked') ? 1 : 0
            };

            $.ajax({
                url: url,
                type: method,
                data: formData,
                success: function(res) {
                    $('#faqModal').modal('hide');
                    faqTable.ajax.reload();
                    alert("FAQ saved successfully!");
                },
                error: function(err) {
                    alert("Something went wrong!");
                    console.error(err);
                }
            });
        });

        window.deleteFaq = function(id) {
            if(confirm('Are you sure you want to delete this FAQ?')) {
                $.ajax({
                    url: `/api/v1/faqs/${id}`,
                    type: 'DELETE',
                    success: function() {
                        faqTable.ajax.reload();
                    }
                });
            }
        }
    </script>
@endpush