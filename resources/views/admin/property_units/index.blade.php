@extends('layout.app')

@section('content')
    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col-md-5">
                <h4 class="mb-0"><i class="fas fa-home text-brand-primary"></i> Property Units / Plots</h4>
            </div>
            <div class="col-md-7 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                <button class="btn btn-success secured-item" data-permission="p_unit_export" id="exportBtn"><i
                        class="fas fa-file-excel"></i> Export</button>
                <button class="btn btn-info secured-item text-white" data-permission="p_unit_print" id="printBtn"><i
                        class="fas fa-print"></i> Print</button>

                <button class="btn btn-dark" onclick="openVisualBuilder()"><i
                        class="fas fa-map-marked-alt text-warning"></i> Open Map Builder</button>
                <button class="btn btn-dark" onclick="openMasterMap()"><i class="fas fa-map text-success"></i> View Master
                    Map</button>

                <button class="btn btn-primary secured-item" data-permission="p_unit_add_direct"
                    onclick="openModal('direct')"><i class="fas fa-plus"></i> Add Unit Manually</button>
                <button class="btn btn-warning secured-item" data-permission="p_unit_add_request"
                    onclick="openModal('request')"><i class="fas fa-paper-plane"></i> Request</button>
                <button class="btn btn-danger secured-item" data-permission="p_unit_delete" id="bulkDeleteBtn"
                    style="display:none;"><i class="fas fa-trash"></i> Delete Selected</button>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card shadow-sm border-0 d-none d-md-block">
            <div class="card-body">
                <table class="table table-hover table-bordered w-100" id="propertyUnitsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%"><input type="checkbox" id="selectAll"></th>
                            <th>Unit Number</th>
                            <th>Category / Area</th>
                            <th>Additional Charges</th>
                            <th>Availability</th>
                            <th>Status</th>
                            <th width="12%">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="d-md-none mt-3" id="mobileCardsContainer"></div>
        <div class="position-fixed bottom-0 start-50 translate-middle-x mb-5 pb-4 z-3" id="mobileFloatingAction"
            style="display: none; width: max-content;">
            <button class="btn btn-danger rounded-pill shadow-lg secured-item px-4" data-permission="p_unit_delete"
                id="mobileBulkDeleteBtn"><i class="fas fa-trash me-2"></i> Delete (<span
                    id="mobileSelectedCount">0</span>)</button>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 🟢 STANDARD FORM MODAL (MANUAL ENTRY) 🟢 -->
    <!-- ============================================================== -->
    <div class="modal fade" id="propertyUnitModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="propertyUnitForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Unit</h5><button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">

                        <div id="scopeContainer" class="row">
                            <div class="col-md-6 mb-3 secured-item" data-permission="public" id="companyWrapper"
                                style="display:none;">
                                <label class="form-label mb-0">Company</label>
                                <select class="form-control select2-modal" id="company_id" style="width:100%;"></select>
                            </div>
                            <div class="col-md-6 mb-3 secured-item" data-permission="public" id="branchWrapper"
                                style="display:none;">
                                <label class="form-label mb-0">Branch</label>
                                <select class="form-control select2-modal" id="branch_id" style="width:100%;"></select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label mb-0">Phase</label>
                                <select class="form-control select2-modal" id="phase_id" style="width:100%;"></select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label mb-0">Property Type</label>
                                <select class="form-control select2-modal" id="property_type_id"
                                    style="width:100%;"></select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label mb-0">Category</label>
                                <select class="form-control select2-modal" id="property_category_id"
                                    style="width:100%;"></select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label mb-0">Area <span class="text-danger">*</span></label>
                                <select class="form-control select2-modal" id="property_area_id" name="property_area_id"
                                    required style="width:100%;"></select>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit / Plot Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="unit_number" name="unit_number"
                                    placeholder="e.g., Plot-101" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Additional Facing Charges</label>
                                <select class="form-control select2-modal" id="charge_ids" name="charge_ids[]"
                                    multiple="multiple" style="width:100%;"></select>
                            </div>
                        </div>

                        <h6 class="mt-2 fw-bold border-bottom pb-2">Boundaries (JSON Logic)</h6>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">East</label>
                                <input type="text" class="form-control" id="bound_east" name="boundaries[east]"
                                    placeholder="e.g., Road 30ft">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">West</label>
                                <input type="text" class="form-control" id="bound_west" name="boundaries[west]"
                                    placeholder="e.g., Plot 102">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">North</label>
                                <input type="text" class="form-control" id="bound_north" name="boundaries[north]"
                                    placeholder="e.g., Park">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">South</label>
                                <input type="text" class="form-control" id="bound_south" name="boundaries[south]"
                                    placeholder="e.g., Main Road">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"
                            id="saveBtn">Save</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 🟢 VISUAL MAP BUILDER MODAL (Full Screen) 🟢 -->
    <!-- ============================================================== -->
    <div class="modal fade" id="mapBuilderModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-light">
                <div class="modal-header bg-dark text-white py-2">
                    <h5 class="modal-title"><i class="fas fa-map-marked-alt text-warning"></i> Visual Layout Builder <span
                            id="builderPhaseName" class="fs-6 ms-2 text-info"></span></h5>
                    <div>
                        <button class="btn btn-sm btn-outline-info me-3" onclick="addCompass()"><i
                                class="fas fa-compass"></i> Add Compass</button>
                        <button class="btn btn-sm btn-outline-warning me-3" onclick="saveLayoutAsImage()"><i
                                class="fas fa-camera"></i> Save as Base Map</button>
                        <button class="btn btn-sm btn-outline-light me-2" onclick="activateDrawMode()"><i
                                class="fas fa-draw-polygon"></i> Draw Custom Shape</button>
                        <button class="btn btn-sm btn-outline-light me-2" onclick="activateSelectMode()"><i
                                class="fas fa-hand-pointer"></i> Select/Move</button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>

                <div class="bg-white p-2 border-bottom d-flex gap-2 align-items-end flex-wrap" id="shapeGeneratorTool">
                    <div>
                        <label class="small text-muted mb-0">Auto Shape</label>
                        <select id="gen_shape" class="form-select form-select-sm">
                            <option value="rectangle">Rectangle / Square</option>
                            <option value="polygon">Custom Polygon (Draw)</option>
                        </select>
                    </div>
                    <div class="gen-rect-inputs">
                        <label class="small text-muted mb-0">Width (L)</label>
                        <input type="number" id="gen_w" class="form-control form-control-sm" placeholder="Width"
                            value="50" style="width: 70px;">
                    </div>
                    <div class="gen-rect-inputs">
                        <label class="small text-muted mb-0">Height (B)</label>
                        <input type="number" id="gen_h" class="form-control form-control-sm" placeholder="Height"
                            value="100" style="width: 70px;">
                    </div>
                    <div class="gen-rect-inputs">
                        <label class="small text-muted mb-0">Qty</label>
                        <input type="number" id="gen_qty" class="form-control form-control-sm" placeholder="Qty"
                            value="1" style="width: 60px;">
                    </div>
                    <div>
                        <button class="btn btn-sm btn-primary" onclick="generateShapes()"><i class="fas fa-magic"></i>
                            Generate</button>
                    </div>

                    <div class="ms-auto d-flex gap-2 align-items-end border-start ps-3">
                        <div>
                            <label class="small text-muted mb-0">Canvas Width</label>
                            <input type="number" id="map_w" class="form-control form-control-sm border-info"
                                value="2000" style="width: 80px;">
                        </div>
                        <div>
                            <label class="small text-muted mb-0">Canvas Height</label>
                            <input type="number" id="map_h" class="form-control form-control-sm border-info"
                                value="1500" style="width: 80px;">
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-info" onclick="resizeArtboard()"><i
                                    class="fas fa-expand-arrows-alt"></i> Update Size</button>
                        </div>
                    </div>
                </div>

                <div class="modal-body p-0 d-flex overflow-hidden">
                    <div class="flex-grow-1 position-relative" id="canvasContainer"
                        style="overflow: auto; background: #d6d8db; cursor: crosshair;">
                        <canvas id="mapCanvas"></canvas>

                        <div class="position-absolute bottom-0 start-0 m-3 bg-white p-2 rounded shadow border"
                            style="z-index: 10; opacity: 0.95; font-size: 11px;">
                            <h6 class="fw-bold mb-2 border-bottom pb-1" style="font-size: 12px;">Map Legend</h6>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(255, 255, 255, 1); border-color:#28a745 !important;"></span>
                                Residential Plot</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(255, 193, 7, 1); border-color:#e0a800 !important;"></span>
                                Commercial</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(100, 100, 100, 1); border-color:#000000 !important;"></span>
                                Road</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(40, 167, 69, 1); border-color:#1e7e34 !important;"></span>
                                Park / Garden</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(253, 126, 20, 1); border-color:#d39e00 !important;"></span>
                                Temple</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(32, 201, 151, 1); border-color:#17a2b8 !important;"></span>
                                Mosque</div>
                            <div class="d-flex align-items-center"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(220, 220, 220, 1); border-style:dashed !important;"></span>
                                Future Extension</div>
                        </div>
                    </div>

                    <div class="bg-white border-start shadow-sm p-3" style="width: 350px; overflow-y: auto;"
                        id="builderRightPanel">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Entity Properties</h6>

                        <form id="visualUnitForm" style="display:none;">
                            <input type="hidden" id="v_edit_id" name="v_edit_id">
                            <input type="hidden" id="v_map_coordinates" name="map_coordinates">

                            <div class="mb-2">
                                <label class="form-label small fw-bold mb-1">Entity Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="v_entity_type" name="entity_type" required
                                    onchange="toggleEntityFields()">
                                    <option value="plot">Residential Plot</option>
                                    <option value="commercial">Commercial Shop</option>
                                    <option value="road">Road</option>
                                    <option value="park">Park / Garden</option>
                                    <option value="temple">Temple</option>
                                    <option value="mosque">Mosque</option>
                                    <option value="future_extension">Future Extension</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold mb-1" id="lbl_unit_number">Plot No / Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="v_unit_number"
                                    name="unit_number" placeholder="e.g., Plot-156 or 30ft Road" required>
                            </div>

                            <div class="p-2 mb-2 rounded bg-light border">
                                <h6 class="fw-bold border-bottom pb-1 small mb-2 text-primary"><i
                                        class="fas fa-paint-brush"></i> Appearance</h6>
                                <div class="row g-1">
                                    <div class="col-4">
                                        <label class="form-label small fw-bold mb-0">Angle(°)</label>
                                        <input type="number" class="form-control form-control-sm px-1" id="v_text_angle"
                                            name="boundaries[text_angle]" placeholder="0" value="0">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-bold mb-0">Font Sz</label>
                                        <input type="number" class="form-control form-control-sm px-1" id="v_text_size"
                                            name="boundaries[text_size]" placeholder="16" value="16">
                                    </div>
                                    <div class="col-4 text-center">
                                        <label class="form-label small fw-bold mb-0">Bg Col</label>
                                        <input type="color"
                                            class="form-control form-control-sm form-control-color w-100 p-0 m-0"
                                            id="v_custom_color" name="boundaries[color]" value="#ffffff"
                                            style="height: 28px;">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <label class="form-label small fw-bold mb-0">Text Color</label>
                                        <input type="color"
                                            class="form-control form-control-sm form-control-color w-100 p-0 m-0"
                                            id="v_text_color" name="boundaries[text_color]" value="#000000"
                                            style="height: 28px;">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 w-100 fw-bold"
                                    onclick="applyColorToAllUnsaved()">
                                    <i class="fas fa-fill-drip"></i> Apply Color to All Unsaved Shapes
                                </button>
                            </div>

                            <div id="plotOnlyFields">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold mb-0">Area <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="v_property_area_id"
                                        name="property_area_id"></select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold mb-0">Additional Charges</label>
                                    <select class="form-select form-select-sm select2-builder" id="v_charge_ids"
                                        name="charge_ids[]" multiple="multiple" style="width:100%;"></select>
                                </div>

                                <h6 class="mt-3 fw-bold border-bottom pb-1 small">Boundaries</h6>
                                <div class="row g-1">
                                    <div class="col-6 mb-1"><input type="text" class="form-control form-control-sm"
                                            id="v_bound_east" name="boundaries[east]" placeholder="East"></div>
                                    <div class="col-6 mb-1"><input type="text" class="form-control form-control-sm"
                                            id="v_bound_west" name="boundaries[west]" placeholder="West"></div>
                                    <div class="col-6 mb-1"><input type="text" class="form-control form-control-sm"
                                            id="v_bound_north" name="boundaries[north]" placeholder="North"></div>
                                    <div class="col-6 mb-1"><input type="text" class="form-control form-control-sm"
                                            id="v_bound_south" name="boundaries[south]" placeholder="South"></div>
                                </div>
                            </div>

                            <div class="mt-3 d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save to
                                    Map</button>
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                    onclick="deleteSelectedShape()"><i class="fas fa-trash"></i> Delete Shape</button>
                            </div>
                        </form>

                        <div id="instructionPanel" class="text-center mt-5 text-muted">
                            <i class="fas fa-mouse-pointer fs-1 mb-3"></i>
                            <p class="small">Use Auto-Shape or "Draw Custom Shape" to create boundaries.</p>
                            <p class="small text-danger fw-bold mt-3 border-top pt-2">
                                <i class="fas fa-search-plus"></i> Mouse Wheel to Zoom <br>
                                <i class="fas fa-arrows-alt"></i> 'ALT' Key + Drag to Move Map
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 🟢 MASTER LAYOUT VIEWER MODAL (Read-Only) 🟢 -->
    <!-- ============================================================== -->
    <div class="modal fade" id="masterMapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-light">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title"><i class="fas fa-map"></i> Master Layout Plan <span id="viewerPhaseName"
                            class="fs-6 ms-2 text-warning"></span></h5>
                    <div>
                        <span class="badge bg-success me-1">Available</span>
                        <span class="badge bg-danger me-1">Booked</span>
                        <span class="badge bg-warning text-dark me-3">Hold</span>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0 d-flex overflow-hidden">
                    <div class="flex-grow-1 position-relative" id="viewerCanvasContainer"
                        style="overflow: auto; background: #e9ecef;">
                        <canvas id="viewerCanvas"></canvas>

                        <div class="position-absolute bottom-0 start-0 m-3 bg-white p-2 rounded shadow border"
                            style="z-index: 10; opacity: 0.95; font-size: 11px;">
                            <h6 class="fw-bold mb-2 border-bottom pb-1" style="font-size: 12px;">Map Legend</h6>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(255, 255, 255, 1); border-color:#28a745 !important;"></span>
                                Residential Plot</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(255, 193, 7, 1); border-color:#e0a800 !important;"></span>
                                Commercial</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(100, 100, 100, 1); border-color:#000000 !important;"></span>
                                Road</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(40, 167, 69, 1); border-color:#1e7e34 !important;"></span>
                                Park / Garden</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(253, 126, 20, 1); border-color:#d39e00 !important;"></span>
                                Temple</div>
                            <div class="d-flex align-items-center mb-1"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(32, 201, 151, 1); border-color:#17a2b8 !important;"></span>
                                Mosque</div>
                            <div class="d-flex align-items-center"><span class="border me-2"
                                    style="width:15px; height:15px; background:rgba(220, 220, 220, 1); border-style:dashed !important;"></span>
                                Future Extension</div>
                        </div>
                    </div>

                    <div class="bg-white border-start shadow-sm p-3" style="width: 320px; display: none;"
                        id="viewerRightPanel">
                        <div class="text-center mb-3 pb-2 border-bottom">
                            <h4 id="vu_name" class="fw-bold text-primary mb-0"></h4>
                            <span id="vu_type" class="badge bg-secondary text-uppercase mt-1"></span>
                        </div>

                        <div id="vu_plot_details">
                            <p class="mb-2"><i class="fas fa-vector-square text-muted me-2"></i> <strong>Area:</strong>
                                <span id="vu_area"></span></p>
                            <p class="mb-2"><i class="fas fa-tags text-muted me-2"></i> <strong>Category:</strong> <span
                                    id="vu_category"></span></p>
                            <p class="mb-2"><i class="fas fa-rupee-sign text-muted me-2"></i> <strong>Charges:</strong>
                                <span id="vu_charges"></span></p>

                            <h6 class="mt-3 fw-bold border-bottom pb-1 small text-muted">Boundaries</h6>
                            <p class="mb-1 small"><strong>East:</strong> <span id="vu_east"></span></p>
                            <p class="mb-1 small"><strong>West:</strong> <span id="vu_west"></span></p>
                            <p class="mb-1 small"><strong>North:</strong> <span id="vu_north"></span></p>
                            <p class="mb-1 small"><strong>South:</strong> <span id="vu_south"></span></p>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded text-center">
                            <h6 class="fw-bold mb-1">Availability</h6>
                            <h5 id="vu_status" class="mb-0 text-uppercase"></h5>
                        </div>
                    </div>

                    <div class="bg-white border-start shadow-sm p-3 d-flex align-items-center justify-content-center text-center"
                        style="width: 320px;" id="viewerPlaceholder">
                        <div class="text-muted">
                            <i class="fas fa-hand-pointer fs-1 mb-3"></i>
                            <p>Click on any plot, road, or park on the map to view its details here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>

    <script>
        // 🟢 1. GLOBAL VARIABLES & UTILITIES 🟢
        let table, globalContext = {},
            allPhasesData = [];
        let canvas, isDrawing = false,
            currentPoints = [],
            activeLine, activeShape;
        let linesArray = [];
        let currentPhaseId = null;
        let viewerCanvas;

        const ENTITY_HEX_COLORS = {
            'plot': '#ffffff',
            'road': '#646464',
            'park': '#28a745',
            'temple': '#fd7e14',
            'mosque': '#20c997',
            'commercial': '#ffc107',
            'future_extension': '#dcdcdc'
        };

        function hexToRgba(hex, alpha) {
            if (!/^#[0-9A-F]{6}$/i.test(hex)) return hex;
            let r = parseInt(hex.slice(1, 3), 16),
                g = parseInt(hex.slice(3, 5), 16),
                b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r},${g},${b},${alpha})`;
        }

        // 🟢 2. DOCUMENT READY BLOCK 🟢
        $(document).ready(function() {
            fetchContextAndSetup();
            $('.select2-modal').select2({
                dropdownParent: $('#propertyUnitModal'),
                placeholder: "Search and Select...",
                allowClear: true,
                width: '100%'
            });

            table = $('#propertyUnitsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/property-units',
                    type: 'GET'
                },
                columns: [{
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: data => `<input type="checkbox" class="row-checkbox" value="${data}">`
                    },
                    {
                        data: 'unit_number',
                        render: data => `<span class="fw-bold text-primary">${data}</span>`
                    },
                    {
                        data: 'area',
                        render: function(data, type, row) {
                            if (!data) return 'N/A';
                            let catName = data.category ? data.category.category_name : 'N/A';
                            return `<span class="fw-bold">${data.area_name} ${data.measurement_unit}</span> <br><small class="text-muted">${catName}</small>`;
                        }
                    },
                    {
                        data: 'charge_names',
                        render: function(data) {
                            if (!data || data.length === 0)
                            return '<span class="text-muted">None</span>';
                            return data.map(c =>
                                `<span class="badge bg-info text-white me-1">${c}</span>`).join(
                                '');
                        }
                    },
                    {
                        data: 'availability_status',
                        render: data => `<span class="badge bg-dark">${data.toUpperCase()}</span>`
                    },
                    {
                        data: 'status',
                        render: data =>
                            `<span class="badge ${data === 'active' ? 'bg-success' : (data === 'pending' ? 'bg-warning' : 'bg-danger')}">${data.toUpperCase()}</span>`
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let buttons = '',
                                isGod = window.userGodMode || false,
                                perms = window.userPerms || [];
                            let rowDataStr = encodeURIComponent(JSON.stringify(row));
                            if (row.status === 'pending') {
                                if (isGod || perms.includes('p_unit_approve')) buttons +=
                                    `<button type="button" class="btn btn-sm btn-success me-1" onclick="actionApprove(${row.id})"><i class="fas fa-check"></i></button>`;
                                if (isGod || perms.includes('p_unit_reject')) buttons +=
                                    `<button type="button" class="btn btn-sm btn-danger me-1" onclick="actionReject(${row.id})"><i class="fas fa-times"></i></button>`;
                            }
                            if (isGod || perms.includes('p_unit_edit')) buttons +=
                                `<button type="button" class="btn btn-sm btn-info me-1 text-white" onclick="editRow(this)" data-row="${rowDataStr}"><i class="fas fa-edit"></i></button>`;
                            return buttons;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    let records = this.api().rows({
                        page: 'current'
                    }).data();
                    let mobileHtml = records.length === 0 ?
                        `<div class="alert alert-secondary text-center">No Records found.</div>` : '';
                    records.each(function(row) {
                        let catName = (row.area && row.area.category) ? row.area.category
                            .category_name : 'N/A';
                        let areaStr = row.area ?
                            `${row.area.area_name} ${row.area.measurement_unit}` : 'N/A';
                        let chargeTags = (row.charge_names && row.charge_names.length) ? row
                            .charge_names.map(c =>
                                `<span class="badge bg-info text-white me-1">${c}</span>`).join(
                                '') : '<span class="text-muted">None</span>';

                        let rowDataStr = encodeURIComponent(JSON.stringify(row));
                        let isGod = window.userGodMode || false,
                            perms = window.userPerms || [];
                        let buttons = '';
                        if (row.status === 'pending') {
                            if (isGod || perms.includes('p_unit_approve')) buttons +=
                                `<button type="button" class="btn btn-sm btn-success me-1" onclick="actionApprove(${row.id})"><i class="fas fa-check"></i></button>`;
                            if (isGod || perms.includes('p_unit_reject')) buttons +=
                                `<button type="button" class="btn btn-sm btn-danger me-1" onclick="actionReject(${row.id})"><i class="fas fa-times"></i></button>`;
                        }
                        if (isGod || perms.includes('p_unit_edit')) buttons +=
                            `<button type="button" class="btn btn-sm btn-info me-1 text-white" onclick="editRow(this)" data-row="${rowDataStr}"><i class="fas fa-edit"></i></button>`;

                        mobileHtml +=
                            `<div class="card shadow-sm border-0 mb-3"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-2"><div class="d-flex align-items-center gap-2"><input type="checkbox" class="mobile-row-checkbox form-check-input mt-0" value="${row.id}" style="width: 1.2rem; height: 1.2rem;"><h6 class="mb-0 fw-bold text-primary">${row.unit_number}</h6></div><span class="badge ${row.status === 'active' ? 'bg-success' : 'bg-warning'}">${row.status.toUpperCase()}</span></div><div class="text-muted small mb-3"><i class="fas fa-list me-1"></i> ${catName} | Area: ${areaStr}<br><i class="fas fa-layer-group me-1"></i> ${chargeTags}<br><i class="fas fa-map me-1"></i> E:${row.boundaries?.east||'-'} W:${row.boundaries?.west||'-'} N:${row.boundaries?.north||'-'} S:${row.boundaries?.south||'-'}</div><div class="d-flex justify-content-end border-top pt-2">${buttons}</div></div></div>`;
                    });
                    $('#mobileCardsContainer').html(mobileHtml);
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            $('#company_id').on('change', function() {
                let id = $(this).val();
                $('#branch_id, #phase_id, #property_type_id, #property_category_id, #property_area_id, #charge_ids')
                    .empty().trigger('change');
                if (id) loadBranches([id]);
            });
            $('#branch_id').on('change', function() {
                let cId = $('#company_id').val() || (globalContext.company_id ? globalContext.company_id
                    .toString() : '');
                let bId = $(this).val() || (globalContext.branch_id ? globalContext.branch_id.toString() :
                    '');
                $('#phase_id, #property_type_id, #property_category_id, #property_area_id, #charge_ids')
                    .empty().trigger('change');
                filterAndLoadPhases([cId], [bId]);
            });
            $('#phase_id').on('change', function() {
                let pId = $(this).val();
                $('#property_type_id, #property_category_id, #property_area_id, #charge_ids').empty()
                    .trigger('change');
                if (pId) {
                    loadTypes([pId]);
                    loadCharges(pId);
                }
            });
            $('#property_type_id').on('change', function() {
                let tId = $(this).val();
                $('#property_category_id, #property_area_id').empty().trigger('change');
                if (tId) loadCategories([tId]);
            });
            $('#property_category_id').on('change', function() {
                let catId = $(this).val();
                $('#property_area_id').empty().trigger('change');
                if (catId) loadAreas([catId]);
            });

            $('#selectAll').on('change', function() {
                $('.row-checkbox').prop('checked', $(this).prop('checked'));
                toggleBulkDeleteBtn();
            });
            $('#propertyUnitsTable').on('change', '.row-checkbox', toggleBulkDeleteBtn);
            $(document).on('change', '.mobile-row-checkbox', function() {
                let c = $('.mobile-row-checkbox:checked').length;
                if (c > 0) {
                    $('#mobileSelectedCount').text(c);
                    $('#mobileFloatingAction').fadeIn();
                } else $('#mobileFloatingAction').fadeOut();
            });

            $('#bulkDeleteBtn, #mobileBulkDeleteBtn').on('click', function() {
                let ids = [];
                if ($(window).width() >= 768) $('.row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                else $('.mobile-row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                if (ids.length === 0) return;
                Swal.fire({
                    title: 'Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#E53E3E',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) $.post('/api/v1/property-units/bulk-delete', {
                        ids: ids
                    }, function(res) {
                        Swal.fire('Deleted!', res.message, 'success');
                        table.ajax.reload(null, false);
                        toggleBulkDeleteBtn();
                        $('#mobileFloatingAction').fadeOut();
                    });
                });
            });

            $('#propertyUnitForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#edit_id').val(),
                    url = id ? `/api/v1/property-units/${id}` : '/api/v1/property-units',
                    method = id ? 'PUT' : 'POST';
                $.ajax({
                    url: url,
                    type: method,
                    data: $(this).serialize(),
                    success: function(res) {
                        $('#propertyUnitModal').modal('hide');
                        Swal.fire('Success', res.message, 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON.message || 'Error occurred',
                            'error');
                    }
                });
            });

            $('#exportBtn').on('click', function() {
                let p = window.location.pathname.split('/')[1];
                let t = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage
                    .getItem('emp_token') || '';
                window.location.href = `/${p}/property-units/export?token=${t}`;
            });
            $('#printBtn').on('click', function() {
                let p = window.location.pathname.split('/')[1];
                let t = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage
                    .getItem('emp_token') || '';
                window.open(`/${p}/property-units/print?token=${t}`, '_blank');
            });

            $(document).on('keyup', function(e) {
                if (e.key === 'Enter' && isDrawing) finishDrawing();
            });

            $('#gen_shape').on('change', function() {
                if ($(this).val() === 'polygon') {
                    $('.gen-rect-inputs').hide();
                    activateDrawMode();
                } else {
                    $('.gen-rect-inputs').show();
                    activateSelectMode();
                }
            });

            // 🔥 NAYA FIX: MATHEMATICALLY PERFECT COORDINATE EXTRACTION 🔥
            $('#visualUnitForm').on('submit', function(e) {
                e.preventDefault();

                let absPoints = [];
                if (activeShape) {
                    if (activeShape.type === 'rect') {
                        absPoints = [{
                                x: activeShape.left,
                                y: activeShape.top
                            },
                            {
                                x: activeShape.left + activeShape.width * activeShape.scaleX,
                                y: activeShape.top
                            },
                            {
                                x: activeShape.left + activeShape.width * activeShape.scaleX,
                                y: activeShape.top + activeShape.height * activeShape.scaleY
                            },
                            {
                                x: activeShape.left,
                                y: activeShape.top + activeShape.height * activeShape.scaleY
                            }
                        ];
                    } else if (activeShape.type === 'polygon' && activeShape.id === 'temp_new') {
                        let matrix = activeShape.calcTransformMatrix();
                        absPoints = activeShape.points.map(p => fabric.util.transformPoint({
                            x: p.x - activeShape.pathOffset.x,
                            y: p.y - activeShape.pathOffset.y
                        }, matrix));
                    } else if (activeShape.type === 'group') {
                        let dx = activeShape.left - activeShape.original_cx;
                        let dy = activeShape.top - activeShape.original_cy;
                        absPoints = activeShape.original_points.map(p => ({
                            x: p.x + dx,
                            y: p.y + dy
                        }));
                    }

                    if (absPoints.length > 0) {
                        $('#v_map_coordinates').val(JSON.stringify(absPoints));
                    }
                }

                let data = $(this).serialize() + `&phase_id=${currentPhaseId}`;
                let submitBtn = $(this).find('button[type="submit"]');

                let editId = $('#v_edit_id').val();
                let method = editId ? 'PUT' : 'POST';
                let url = editId ? `/api/v1/property-units/${editId}` : '/api/v1/property-units';

                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: url,
                    type: method,
                    data: data,
                    success: function(res) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: editId ? 'Updated Successfully!' :
                                'Saved! Setup next plot.',
                            showConfirmButton: false,
                            timer: 2000
                        });

                        if (activeShape) {
                            let type = $('#v_entity_type').val();
                            let unitName = $('#v_unit_number').val();
                            let textAngle = parseFloat($('#v_text_angle').val()) || 0;
                            let textSize = parseFloat($('#v_text_size').val()) || 16;
                            let customColor = $('#v_custom_color').val() || '#ffffff';
                            let customTextColor = $('#v_text_color').val() || '#000000';
                            let finalId = editId ? editId : res.unit_id;

                            let newData = {
                                id: finalId,
                                entity_type: type,
                                unit_number: unitName,
                                map_coordinates: $('#v_map_coordinates').val(),
                                availability_status: activeShape.db_data ? activeShape
                                    .db_data.availability_status : 'available',
                                boundaries: {
                                    text_angle: textAngle,
                                    text_size: textSize,
                                    color: customColor,
                                    text_color: customTextColor,
                                    east: $('#v_bound_east').val(),
                                    west: $('#v_bound_west').val(),
                                    north: $('#v_bound_north').val(),
                                    south: $('#v_bound_south').val()
                                }
                            };

                            // Use our central rendering engine for consistency
                            let newGroup = createEntityGroup(newData, canvas, false);
                            if (newGroup) canvas.remove(activeShape);
                            canvas.renderAll();
                        }

                        $('#visualUnitForm')[0].reset();
                        $('#v_edit_id').val('');
                        $('#visualUnitForm').hide();
                        submitBtn.prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Save to Map');
                        if (typeof table !== 'undefined') table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        submitBtn.prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Save to Map');
                        Swal.fire('Error', 'Missing required fields or Server Error', 'error');
                    }
                });
            });
        });

        // 🟢 3. GLOBAL FUNCTIONS 🟢
        window.toggleBulkDeleteBtn = function() {
            $('#bulkDeleteBtn').toggle($('.row-checkbox:checked').length > 0);
        }

        window.fetchContextAndSetup = function() {
            $.get('/api/v1/context', function(res) {
                globalContext = res;
                if (res.is_god) {
                    $('#companyWrapper, #branchWrapper').show();
                    loadCompanies();
                } else if (res.is_director) {
                    $('#branchWrapper').show();
                    loadBranches([res.company_id]);
                } else cacheAllPhases(res.company_id, res.branch_id);
            });
        }

        window.loadCompanies = function() {
            $.get('/api/v1/get-active-companies', function(res) {
                let o = '<option value="">Select Company</option>';
                if (res.data) res.data.forEach(c => {
                    o += `<option value="${c.id}">${c.company_name}</option>`;
                });
                $('#company_id').html(o).trigger('change');
            });
        }

        window.loadBranches = function(cIds) {
            let o = '<option value="">Select Branch</option>';
            $('#company_id option:selected').each(function() {
                let v = $(this).val();
                if (v) o += `<option value="HO_${v}">Head Office (${$(this).text()})</option>`;
            });
            if (!cIds || !cIds.length) {
                $('#branch_id').html(o).trigger('change');
                cacheAllPhases([]);
                return;
            }
            Promise.all(cIds.map(id => new Promise(res => $.get(`/api/v1/phases/get-branches/${id}`, r => res(r.data ||
                [])).fail(() => res([]))))).then(results => {
                results.forEach(arr => arr.forEach(b => o +=
                    `<option value="${b.id}">${b.branch_name}</option>`));
                $('#branch_id').html(o).trigger('change');
                cacheAllPhases(cIds);
            });
        }

        window.cacheAllPhases = function(cIds) {
            $.get('/api/v1/phases', function(res) {
                if (res.success && res.data) {
                    allPhasesData = res.data;
                    filterAndLoadPhases(cIds, $('#branch_id').val() ? [$('#branch_id').val()] : []);
                }
            });
        }

        window.filterAndLoadPhases = function(cIds, bIds) {
            let o = '<option value="">Select Phase</option>';
            allPhasesData.forEach(p => {
                let c = p.company_id ? p.company_id.toString() : '',
                    b = p.branch_id ? p.branch_id.toString() : '';
                if ((!cIds.length || cIds.includes(c)) && (!bIds.length || (b === "" && bIds.includes("HO_" +
                        c)) || bIds.includes(b))) {
                    o +=
                    `<option value="${p.id}">${p.phase_name} (${p.branch ? p.branch.branch_name : 'HO'})</option>`;
                }
            });
            $('#phase_id').html(o).trigger('change');
        }

        window.loadTypes = function(pIds) {
            let o = '<option value="">Select Type</option>';
            Promise.all(pIds.map(id => new Promise(res => $.get(`/api/v1/property-dependencies/types/${id}`, r => res(r
                .data || [])).fail(() => res([]))))).then(results => {
                results.forEach(arr => arr.forEach(t => o +=
                `<option value="${t.id}">${t.type_name}</option>`));
                $('#property_type_id').html(o).trigger('change');
            });
        }

        window.loadCategories = function(tIds) {
            let o = '<option value="">Select Category</option>';
            Promise.all(tIds.map(id => new Promise(res => $.get(`/api/v1/property-dependencies/categories/${id}`, r =>
                res(r.data || [])).fail(() => res([]))))).then(results => {
                results.forEach(arr => arr.forEach(c => o +=
                    `<option value="${c.id}">${c.category_name}</option>`));
                $('#property_category_id').html(o).trigger('change');
            });
        }

        window.loadAreas = function(catIds) {
            let o = '<option value="">Select Area</option>';
            Promise.all(catIds.map(id => new Promise(res => $.get(`/api/v1/property-dependencies/areas/${id}`, r => res(
                r.data || [])).fail(() => res([]))))).then(results => {
                results.forEach(arr => arr.forEach(a => o +=
                    `<option value="${a.id}">${a.area_name} ${a.measurement_unit}</option>`));
                $('#property_area_id').html(o).trigger('change');
            });
        }

        window.loadCharges = function(phaseId) {
            let o = '';
            $.get(`/api/v1/property-dependencies/charges/${phaseId}`, function(res) {
                if (res.success && res.data) {
                    res.data.forEach(c => {
                        o +=
                        `<option value="${c.id}">${c.charge_name} (+${c.charge_percentage}%)</option>`;
                    });
                }
                $('#charge_ids').html(o).trigger('change');
            });
        }

        window.openModal = function(type) {
            $('#propertyUnitForm')[0].reset();
            $('#edit_id').val('');
            $('#company_id, #branch_id, #phase_id, #property_type_id, #property_category_id, #property_area_id, #charge_ids')
                .val(null).trigger('change');
            $('#modalTitle').text(type === 'direct' ? 'Add Unit' : 'Request Unit');
            $('#saveBtn').text(type === 'direct' ? 'Save' : 'Submit Request');
            $('#propertyUnitModal').modal('show');
        };

        window.editRow = function(btn) {
            let row = JSON.parse(decodeURIComponent($(btn).data('row')));
            $('#propertyUnitForm')[0].reset();
            $('#edit_id').val(row.id);
            $('#unit_number').val(row.unit_number);
            $('#bound_east').val(row.boundaries?.east || '');
            $('#bound_west').val(row.boundaries?.west || '');
            $('#bound_north').val(row.boundaries?.north || '');
            $('#bound_south').val(row.boundaries?.south || '');

            let cId = row.company_id,
                cName = row.company ? row.company.company_name : 'Selected Company';
            let bId = row.branch_id,
                bName = row.branch ? row.branch.branch_name : `Head Office (${cName})`,
                bVal = bId ? bId : `HO_${cId}`;

            let areaId = row.property_area_id,
                areaName = row.area ? `${row.area.area_name} ${row.area.measurement_unit}` : 'Selected Area';
            let catId = row.area ? row.area.property_category_id : '',
                catName = (row.area && row.area.category) ? row.area.category.category_name : 'Selected Category';
            let tId = (row.area && row.area.category) ? row.area.category.property_type_id : '',
                tName = (row.area && row.area.category && row.area.category.propertyType) ? row.area.category
                .propertyType.type_name : 'Selected Type';
            let pId = (row.area && row.area.category && row.area.category.propertyType) ? row.area.category.propertyType
                .phase_id : '',
                pName = (row.area && row.area.category && row.area.category.propertyType && row.area.category
                    .propertyType.phase) ? row.area.category.propertyType.phase.phase_name : 'Selected Phase';

            if (!$('#company_id option[value="' + cId + '"]').length) $('#company_id').append(new Option(cName, cId,
                true, true));
            $('#company_id').val(cId).trigger('change');
            setTimeout(() => {
                if (!$('#branch_id option[value="' + bVal + '"]').length) $('#branch_id').append(new Option(
                    bName, bVal, true, true));
                $('#branch_id').val(bVal).trigger('change');
                setTimeout(() => {
                    if (!$('#phase_id option[value="' + pId + '"]').length) $('#phase_id').append(
                        new Option(pName, pId, true, true));
                    $('#phase_id').val(pId).trigger('change');
                    loadCharges(pId);
                    setTimeout(() => {
                        if (row.charge_ids) $('#charge_ids').val(row.charge_ids).trigger(
                            'change');
                        if (!$('#property_type_id option[value="' + tId + '"]').length) $(
                            '#property_type_id').append(new Option(tName, tId, true, true));
                        $('#property_type_id').val(tId).trigger('change');
                        setTimeout(() => {
                            if (!$('#property_category_id option[value="' + catId +
                                    '"]').length) $('#property_category_id').append(
                                new Option(catName, catId, true, true));
                            $('#property_category_id').val(catId).trigger('change');
                            setTimeout(() => {
                                if (!$('#property_area_id option[value="' +
                                        areaId + '"]').length) $(
                                    '#property_area_id').append(new Option(
                                    areaName, areaId, true, true));
                                $('#property_area_id').val(areaId).trigger(
                                    'change');
                            }, 250);
                        }, 250);
                    }, 250);
                }, 250);
            }, 250);
            $('#modalTitle').text('Edit Unit');
            $('#saveBtn').text('Update');
            $('#propertyUnitModal').modal('show');
        };

        window.actionApprove = function(id) {
            $.post(`/api/v1/property-units/${id}/approve`, res => {
                table.ajax.reload(null, false);
                Swal.fire('Approved!', res.message, 'success');
            });
        };
        window.actionReject = function(id) {
            $.post(`/api/v1/property-units/${id}/reject`, res => {
                table.ajax.reload(null, false);
                Swal.fire('Rejected!', res.message, 'success');
            });
        };

        // 🟢 VISUAL MAP BUILDER FUNCTIONS 🟢
        window.openVisualBuilder = function() {
            $.get('/api/v1/phases', function(res) {
                if (!res.success || res.data.length === 0) return Swal.fire('Warning', 'No phases available.',
                    'warning');
                let phaseOptions = {};
                res.data.forEach(p => {
                    phaseOptions[p.id] = `${p.phase_name} (${p.branch ? p.branch.branch_name : 'HO'})`;
                });

                Swal.fire({
                    title: 'Select Phase',
                    text: 'Choose a phase to open Map Builder',
                    input: 'select',
                    inputOptions: phaseOptions,
                    inputPlaceholder: 'Search and select phase...',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-map-marked-alt"></i> Load Map'
                }).then((result) => {
                    if (result.isConfirmed && result.value) loadMapBuilderData(result.value);
                });
            });
        };

        window.loadMapBuilderData = function(phaseId) {
            currentPhaseId = phaseId;
            $('#builderPhaseName').text(' - Loading...');
            $('.select2-builder').select2({
                dropdownParent: $('#mapBuilderModal')
            });

            let oArea = '<option value="">Select Area...</option>';
            $.get(`/api/v1/property-dependencies/phase-areas/${phaseId}`, function(res) {
                if (res.success && res.data) {
                    res.data.forEach(a => {
                        oArea += `<option value="${a.id}">${a.name}</option>`;
                    });
                }
                $('#v_property_area_id').html(oArea).trigger('change');
            });

            let oCharge = '';
            $.get(`/api/v1/property-dependencies/charges/${phaseId}`, function(res) {
                if (res.success && res.data) {
                    res.data.forEach(c => {
                        oCharge +=
                            `<option value="${c.id}">${c.charge_name} (+${c.charge_percentage}%)</option>`;
                    });
                }
                $('#v_charge_ids').html(oCharge).trigger('change');
            });

            $.get(`/api/v1/property-units/phase-map/${phaseId}`, function(res) {
                $('#builderPhaseName').text(` (Phase ID: ${phaseId})`);
                $('#mapBuilderModal').modal('show');

                if (!res.success || !res.map_url) {
                    Swal.fire({
                        title: 'No Map Found',
                        text: 'Create a blank layout from scratch?',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Create Blank Layout',
                        html: `<p class="text-muted small">Enter dimensions for your new layout (Pixels)</p><input id="swal-w" class="swal2-input" placeholder="Width" value="2000"><input id="swal-h" class="swal2-input" placeholder="Height" value="1500">`,
                        preConfirm: () => {
                            return {
                                w: parseFloat($('#swal-w').val()) || 2000,
                                h: parseFloat($('#swal-h').val()) || 1500
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) setTimeout(() => {
                            initBlankCanvas(result.value.w, result.value.h, res.units);
                        }, 500);
                        else $('#mapBuilderModal').modal('hide');
                    });
                } else {
                    setTimeout(() => {
                        initCanvas(res.map_url, res.units);
                    }, 500);
                }
            });
        }

        window.resizeArtboard = function() {
            if (!canvas) return;
            let container = document.getElementById('canvasContainer');
            let newW = parseFloat($('#map_w').val());
            let newH = parseFloat($('#map_h').val());

            if (!newW || !newH || newW <= 0 || newH <= 0) return Swal.fire('Error',
                'Please enter valid width and height.', 'error');

            let artboard = canvas.getObjects().find(o => o.id === 'artboard_bg');
            let oldGrid = canvas.getObjects().find(o => o.id === 'grid_bg');

            let zoomX = container.clientWidth / (newW + 100);
            let zoomY = container.clientHeight / (newH + 100);
            let zoom = Math.min(zoomX, zoomY);

            if (artboard) artboard.set({
                width: newW,
                height: newH,
                strokeWidth: 4 / zoom
            });
            if (oldGrid) canvas.remove(oldGrid);

            let gridSize = 50;
            let pathData = [];
            for (let i = 0; i <= newW; i += gridSize) {
                pathData.push(`M ${i} 0 L ${i} ${newH}`);
            }
            for (let i = 0; i <= newH; i += gridSize) {
                pathData.push(`M 0 ${i} L ${newW} ${i}`);
            }

            let gridPath = new fabric.Path(pathData.join(' '), {
                fill: '',
                stroke: '#cccccc',
                strokeWidth: 1.5 / zoom,
                selectable: false,
                evented: false,
                id: 'grid_bg',
                objectCaching: false
            });

            canvas.add(gridPath);
            gridPath.sendToBack();
            if (artboard) artboard.sendToBack();

            canvas.setZoom(zoom);
            let vpt = canvas.viewportTransform;
            vpt[4] = (container.clientWidth - newW * zoom) / 2;
            vpt[5] = (container.clientHeight - newH * zoom) / 2;

            canvas.renderAll();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Map resized to ${newW}x${newH}`,
                showConfirmButton: false,
                timer: 3000
            });
        };

        window.attachCanvasEvents = function(targetCanvas) {
            targetCanvas.on('mouse:wheel', function(opt) {
                let delta = opt.e.deltaY;
                let zoom = targetCanvas.getZoom() * (0.999 ** delta);
                if (zoom > 20) zoom = 20;
                if (zoom < 0.1) zoom = 0.1;
                targetCanvas.zoomToPoint({
                    x: opt.e.offsetX,
                    y: opt.e.offsetY
                }, zoom);
                opt.e.preventDefault();
                opt.e.stopPropagation();
            });

            // 🚀 MAGNETIC SNAP TO GRID 🚀
            targetCanvas.on('object:moving', function(options) {
                let obj = options.target;
                if (obj && obj.id !== 'artboard_bg' && obj.id !== 'grid_bg') {
                    obj.set({
                        left: Math.round(obj.left / 50) * 50,
                        top: Math.round(obj.top / 50) * 50
                    });
                }
            });

            targetCanvas.on('mouse:down', function(opt) {
                let evt = opt.e;
                if (!isDrawing && evt.altKey === true) {
                    this.isDragging = true;
                    this.selection = false;
                    this.lastPosX = evt.clientX;
                    this.lastPosY = evt.clientY;
                }
            });
            targetCanvas.on('mouse:move', function(opt) {
                if (this.isDragging) {
                    let e = opt.e;
                    let vpt = this.viewportTransform;
                    vpt[4] += e.clientX - this.lastPosX;
                    vpt[5] += e.clientY - this.lastPosY;
                    this.requestRenderAll();
                    this.lastPosX = e.clientX;
                    this.lastPosY = e.clientY;
                }
            });
            targetCanvas.on('mouse:up', function(opt) {
                this.setViewportTransform(this.viewportTransform);
                this.isDragging = false;
                this.selection = true;
            });
        }

        window.initBlankCanvas = function(width, height, existingUnits) {
            let container = document.getElementById('canvasContainer');
            $(container).empty().append('<canvas id="mapCanvas"></canvas>');
            canvas = new fabric.Canvas('mapCanvas', {
                width: container.clientWidth,
                height: container.clientHeight,
                backgroundColor: '#d6d8db',
                selection: false
            });

            $('#map_w').val(width);
            $('#map_h').val(height);

            let zoomX = container.clientWidth / (width + 100);
            let zoomY = container.clientHeight / (height + 100);
            let zoom = Math.min(zoomX, zoomY);

            let artboard = new fabric.Rect({
                left: 0,
                top: 0,
                width: width,
                height: height,
                fill: '#ffffff',
                stroke: '#000000',
                strokeWidth: 4 / zoom,
                selectable: false,
                evented: false,
                id: 'artboard_bg',
                objectCaching: false
            });
            canvas.add(artboard);

            let gridSize = 50;
            let pathData = [];
            for (let i = 0; i <= width; i += gridSize) {
                pathData.push(`M ${i} 0 L ${i} ${height}`);
            }
            for (let i = 0; i <= height; i += gridSize) {
                pathData.push(`M 0 ${i} L ${width} ${i}`);
            }
            let gridPath = new fabric.Path(pathData.join(' '), {
                fill: '',
                stroke: '#cccccc',
                strokeWidth: 1.5 / zoom,
                selectable: false,
                evented: false,
                id: 'grid_bg',
                objectCaching: false
            });
            canvas.add(gridPath);

            canvas.setZoom(zoom);
            let vpt = canvas.viewportTransform;
            vpt[4] = (container.clientWidth - width * zoom) / 2;
            vpt[5] = (container.clientHeight - height * zoom) / 2;

            renderExistingUnits(existingUnits, canvas);
            attachCanvasEvents(canvas);
            attachDrawingEvents(canvas);
        }

        // 🔥 FIX: Background Image IGNORE logic so DB shapes don't duplicate visually 🔥
        window.initCanvas = function(imageUrl, existingUnits) {
            let container = document.getElementById('canvasContainer');

            fabric.Image.fromURL(imageUrl, function(img) {
                let w = img.width;
                let h = img.height;

                // Agar image generated_layout hai, toh usko background mat banao (Double vision rokne ke liye)
                if (imageUrl.includes('generated_layout_phase_')) {
                    initBlankCanvas(w, h, existingUnits);
                    return;
                }

                $(container).empty().append('<canvas id="mapCanvas"></canvas>');
                canvas = new fabric.Canvas('mapCanvas', {
                    width: container.clientWidth,
                    height: container.clientHeight,
                    backgroundColor: '#d6d8db',
                    selection: false
                });

                $('#map_w').val(w);
                $('#map_h').val(h);

                let zoomX = container.clientWidth / (w + 100);
                let zoomY = container.clientHeight / (h + 100);
                let zoom = Math.min(zoomX, zoomY);

                img.set({
                    left: 0,
                    top: 0,
                    originX: 'left',
                    originY: 'top',
                    selectable: false,
                    evented: false,
                    id: 'artboard_bg',
                    objectCaching: false
                });
                canvas.add(img);
                img.sendToBack();

                let gridSize = 50;
                let pathData = [];
                for (let i = 0; i <= w; i += gridSize) {
                    pathData.push(`M ${i} 0 L ${i} ${h}`);
                }
                for (let i = 0; i <= h; i += gridSize) {
                    pathData.push(`M 0 ${i} L ${w} ${i}`);
                }
                let gridPath = new fabric.Path(pathData.join(' '), {
                    fill: '',
                    stroke: '#cccccc',
                    strokeWidth: 1.5 / zoom,
                    selectable: false,
                    evented: false,
                    id: 'grid_bg',
                    objectCaching: false
                });
                canvas.add(gridPath);

                canvas.setZoom(zoom);
                let vpt = canvas.viewportTransform;
                vpt[4] = (container.clientWidth - w * zoom) / 2;
                vpt[5] = (container.clientHeight - h * zoom) / 2;

                renderExistingUnits(existingUnits, canvas);
                attachCanvasEvents(canvas);
                attachDrawingEvents(canvas);
                canvas.renderAll();
            });
        }

        // 🔥 FIX: 100% Mathematically Perfect Rendering Engine 🔥
        window.createEntityGroup = function(unitData, targetCanvas, isViewer) {
            let points = typeof unitData.map_coordinates === 'string' ? JSON.parse(unitData.map_coordinates) : unitData
                .map_coordinates;
            if (!Array.isArray(points) || points.length === 0) return null;

            let bgColor = unitData.boundaries?.color || ENTITY_HEX_COLORS[unitData.entity_type] || '#ffffff';
            let txtColor = unitData.boundaries?.text_color || '#000000';
            let fillCol = hexToRgba(bgColor, 1);
            let strokeCol = bgColor;

            if (unitData.entity_type === 'plot' || unitData.entity_type === 'commercial') {
                if (unitData.availability_status === 'booked') {
                    fillCol = 'rgba(220,53,69,1)';
                    strokeCol = '#bd2130';
                } else if (unitData.availability_status === 'hold') {
                    fillCol = 'rgba(255,193,7,1)';
                    strokeCol = '#d39e00';
                }
            }

            let minX = Math.min(...points.map(p => p.x));
            let minY = Math.min(...points.map(p => p.y));
            let maxX = Math.max(...points.map(p => p.x));
            let maxY = Math.max(...points.map(p => p.y));
            let cx = minX + (maxX - minX) / 2;
            let cy = minY + (maxY - minY) / 2;

            let relativePoints = points.map(p => ({
                x: p.x - cx,
                y: p.y - cy
            }));

            let poly = new fabric.Polygon(relativePoints, {
                fill: fillCol,
                stroke: strokeCol,
                strokeWidth: 2,
                originX: 'center',
                originY: 'center',
                left: 0,
                top: 0
            });

            let textAngle = unitData.boundaries?.text_angle ? parseFloat(unitData.boundaries.text_angle) : 0;
            let textSize = unitData.boundaries?.text_size ? parseFloat(unitData.boundaries.text_size) : 16;

            let text = new fabric.Text(unitData.unit_number, {
                left: 0,
                top: 0,
                fontSize: textSize,
                fill: txtColor,
                originX: 'center',
                originY: 'center',
                fontWeight: 'bold',
                angle: textAngle
            });

            let group = new fabric.Group([poly, text], {
                left: cx,
                top: cy,
                originX: 'center',
                originY: 'center',
                selectable: !isViewer,
                evented: true,
                hasControls: false,
                db_data: unitData,
                db_id: unitData.id,
                original_cx: cx,
                original_cy: cy,
                original_points: points
            });

            if (isViewer) {
                group.on('mousedown', function() {
                    showViewerDetails(this.db_data);
                });
            }

            targetCanvas.add(group);
            return group;
        }

        window.renderExistingUnits = function(existingUnits, targetCanvas) {
            if (existingUnits && existingUnits.length > 0) {
                existingUnits.forEach(unit => {
                    createEntityGroup(unit, targetCanvas, targetCanvas === viewerCanvas);
                });
            }
        }

        window.attachDrawingEvents = function(targetCanvas) {
            targetCanvas.on('mouse:down', function(options) {
                if (!isDrawing) return;
                let pointer = targetCanvas.getPointer(options.e);
                let x = Math.round(pointer.x / 50) * 50;
                let y = Math.round(pointer.y / 50) * 50;

                currentPoints.push({
                    x: x,
                    y: y
                });
                let circle = new fabric.Circle({
                    radius: 3,
                    fill: 'red',
                    left: x,
                    top: y,
                    originX: 'center',
                    originY: 'center',
                    selectable: false
                });
                targetCanvas.add(circle);

                if (currentPoints.length > 1) {
                    let prev = currentPoints[currentPoints.length - 2];
                    let line = new fabric.Line([prev.x, prev.y, x, y], {
                        strokeWidth: 2,
                        fill: 'red',
                        stroke: 'red',
                        selectable: false
                    });
                    linesArray.push(line);
                    targetCanvas.add(line);
                }
                activeLine = new fabric.Line([x, y, x, y], {
                    strokeWidth: 2,
                    fill: 'red',
                    stroke: 'red',
                    strokeDashArray: [5, 5],
                    selectable: false
                });
                targetCanvas.add(activeLine);
            });

            targetCanvas.on('mouse:move', function(options) {
                if (!isDrawing || !activeLine) return;
                let pointer = targetCanvas.getPointer(options.e);
                let snapX = Math.round(pointer.x / 50) * 50;
                let snapY = Math.round(pointer.y / 50) * 50;
                activeLine.set({
                    x2: snapX,
                    y2: snapY
                });
                targetCanvas.renderAll();
            });

            targetCanvas.on('mouse:dblclick', finishDrawing);
            targetCanvas.on('selection:created', handleSelection);
            targetCanvas.on('selection:updated', handleSelection);
            targetCanvas.on('selection:cleared', function() {
                $('#v_edit_id').val('');
                $('#visualUnitForm').hide();
                $('#instructionPanel').show();
                activeShape = null;
            });
        }

        window.activateDrawMode = function() {
            isDrawing = true;
            canvas.defaultCursor = 'crosshair';
            canvas.selection = false;
            canvas.discardActiveObject();
            canvas.renderAll();
            $('#visualUnitForm').hide();
            $('#instructionPanel').show();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Draw Mode! Click to add points.',
                showConfirmButton: false,
                timer: 3000
            });
        };

        window.activateSelectMode = function() {
            isDrawing = false;
            canvas.defaultCursor = 'default';
            canvas.selection = true;
        };

        window.finishDrawing = function() {
            if (currentPoints.length < 3) return;
            isDrawing = false;
            canvas.remove(activeLine);
            linesArray.forEach(l => canvas.remove(l));
            canvas.getObjects('circle').forEach(c => canvas.remove(c));

            let polygon = new fabric.Polygon(currentPoints, {
                fill: 'rgba(0,0,0,0)',
                stroke: 'blue',
                strokeWidth: 2,
                selectable: true,
                hasControls: false,
                hasBorders: true,
                id: 'temp_new',
                objectCaching: false
            });

            canvas.add(polygon);
            canvas.setActiveObject(polygon);
            activeShape = polygon;
            $('#v_map_coordinates').val(JSON.stringify(currentPoints));
            $('#v_edit_id').val('');
            $('#visualUnitForm')[0].reset();
            $('#instructionPanel').hide();
            $('#visualUnitForm').show();
            toggleEntityFields();

            currentPoints = [];
            linesArray = [];
            activeLine = null;
            activateSelectMode();
        }

        window.handleSelection = function(e) {
            let obj = e.selected[0];
            if (!obj) return;
            if (obj.id === 'compass_icon' || obj.id === 'artboard_bg' || obj.id === 'grid_bg') return;

            $('#instructionPanel').hide();
            $('#visualUnitForm').show();
            activeShape = obj;

            if (obj.type === 'group' && obj.db_data) {
                let data = obj.db_data;
                $('#v_edit_id').val(data.id);

                $('#v_unit_number').val(data.unit_number);
                $('#v_entity_type').val(data.entity_type).trigger('change');
                $('#v_map_coordinates').val(JSON.stringify(data.map_coordinates));

                $('#v_text_angle').val(data.boundaries?.text_angle || 0);
                $('#v_text_size').val(data.boundaries?.text_size || 16);

                $('#v_custom_color').val(data.boundaries?.color || ENTITY_HEX_COLORS[data.entity_type] || '#ffffff');
                $('#v_text_color').val(data.boundaries?.text_color || '#000000');

                $('#v_bound_east').val(data.boundaries?.east || '');
                $('#v_bound_west').val(data.boundaries?.west || '');
                $('#v_bound_north').val(data.boundaries?.north || '');
                $('#v_bound_south').val(data.boundaries?.south || '');
            } else {
                $('#v_edit_id').val('');
                if (obj.temp_custom_color) $('#v_custom_color').val(obj.temp_custom_color);
                if (obj.temp_text_color) $('#v_text_color').val(obj.temp_text_color);
            }
        }

        window.toggleEntityFields = function() {
            let type = $('#v_entity_type').val();

            $('#v_custom_color').val(ENTITY_HEX_COLORS[type] || '#ffffff');

            if (type === 'plot') {
                $('#plotOnlyFields').slideDown();
                $('#lbl_unit_number').html('Plot Number <span class="text-danger">*</span>');
                $('#v_unit_number').attr('placeholder', 'e.g., Plot-156');
            } else if (type === 'commercial') {
                $('#plotOnlyFields').slideDown();
                $('#lbl_unit_number').html('Shop/Complex Name <span class="text-danger">*</span>');
                $('#v_unit_number').attr('placeholder', 'e.g., Big Bazaar');
            } else {
                $('#plotOnlyFields').slideUp();
                $('#lbl_unit_number').html('Entity Name <span class="text-danger">*</span>');
                $('#v_unit_number').attr('placeholder', 'e.g., Central Park / 30ft Road');
            }
        };

        window.generateShapes = function() {
            let type = $('#gen_shape').val();
            if (type === 'polygon') return activateDrawMode();

            let w = parseFloat($('#gen_w').val()) || 50,
                h = parseFloat($('#gen_h').val()) || 80;
            let qty = parseInt($('#gen_qty').val()) || 1;
            let center = canvas.getVpCenter();

            for (let i = 0; i < qty; i++) {
                let snapLeft = Math.round((center.x + (i * 50)) / 50) * 50;
                let snapTop = Math.round((center.y + (i * 50)) / 50) * 50;

                let rect = new fabric.Rect({
                    left: snapLeft,
                    top: snapTop,
                    width: w,
                    height: h,
                    fill: 'rgba(0, 123, 255, 0.3)',
                    stroke: '#007bff',
                    strokeWidth: 2,
                    hasBorders: true,
                    hasControls: true,
                    isNewGenerated: true,
                    objectCaching: false
                });
                canvas.add(rect);
            }
            canvas.renderAll();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `${qty} shapes generated! Drag to position.`,
                showConfirmButton: false,
                timer: 3000
            });
            activateSelectMode();
        };

        window.applyColorToAllUnsaved = function() {
            let customColor = $('#v_custom_color').val();
            let txtColor = $('#v_text_color').val();
            let fillCol = hexToRgba(customColor, 1);
            let strokeCol = customColor;

            let count = 0;
            canvas.getObjects().forEach(function(obj) {
                if (obj.isNewGenerated || obj.id === 'temp_new') {
                    if (obj.type === 'group') {
                        obj._objects[0].set({
                            fill: fillCol,
                            stroke: strokeCol
                        });
                        obj._objects[1].set({
                            fill: txtColor
                        });
                    } else {
                        obj.set({
                            fill: fillCol,
                            stroke: strokeCol
                        });
                    }
                    obj.temp_custom_color = customColor;
                    obj.temp_text_color = txtColor;
                    count++;
                }
            });
            canvas.renderAll();
            if (count > 0) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Color applied to ${count} unsaved shapes!`,
                    showConfirmButton: false,
                    timer: 2000
                });
            } else {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'No unsaved shapes found.',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        };

        window.addCompass = function() {
            let svgCompass = `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="45" fill="%23ffffff" stroke="%23333333" stroke-width="3"/>
            <polygon points="50,10 60,50 50,45 40,50" fill="%23e74c3c"/>
            <polygon points="50,90 60,50 50,55 40,50" fill="%2395a5a6"/>
            <text x="50" y="28" font-family="Arial" font-size="14" font-weight="bold" fill="%23ffffff" text-anchor="middle">N</text>
        </svg>`;

            fabric.Image.fromURL(svgCompass, function(img) {
                let center = canvas.getVpCenter();
                img.set({
                    left: center.x,
                    top: center.y,
                    originX: 'center',
                    originY: 'center',
                    scaleX: 1,
                    scaleY: 1,
                    hasBorders: true,
                    hasControls: true,
                    id: 'compass_icon',
                    objectCaching: false
                });
                canvas.add(img);
                canvas.setActiveObject(img);
                canvas.renderAll();
                activateSelectMode();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Compass Added! Drag to corner and rotate to set North.',
                    showConfirmButton: false,
                    timer: 4000
                });
            });
        };

        // 🔥 NAYA FIX: Export Image Without Grid and Full HD 🔥
        window.saveLayoutAsImage = function() {
            if (!canvas) return;
            Swal.fire({
                title: 'Generating Map...',
                html: 'Baking layout into a high-res image!',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            let grid = canvas.getObjects().find(o => o.id === 'grid_bg');
            if (grid) canvas.remove(grid);

            let artboard = canvas.getObjects().find(o => o.id === 'artboard_bg');

            // Hide ALL DB Shapes from image to prevent double rendering in future
            let dbShapes = canvas.getObjects().filter(o => o.db_data || o.db_id || o.isNewGenerated || o.id ===
                'temp_new');

            canvas.discardActiveObject();

            let originalVpt = canvas.viewportTransform.slice();
            canvas.setViewportTransform([1, 0, 0, 1, 0, 0]);
            canvas.renderAll();

            let exportOptions = {
                format: 'jpeg',
                quality: 1,
                multiplier: 2
            };
            if (artboard) {
                exportOptions.left = artboard.left;
                exportOptions.top = artboard.top;
                exportOptions.width = artboard.width * (artboard.scaleX || 1);
                exportOptions.height = artboard.height * (artboard.scaleY || 1);
            }

            let dataURL = canvas.toDataURL(exportOptions);

            // Restore canvas state
            canvas.setViewportTransform(originalVpt);
            if (grid) {
                canvas.add(grid);
                grid.sendToBack();
                if (artboard) artboard.sendToBack();
            }
            canvas.renderAll();

            $.ajax({
                url: '/api/v1/property-units/save-layout-map',
                type: 'POST',
                data: {
                    phase_id: currentPhaseId,
                    image_base64: dataURL,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    Swal.fire('Success!', 'Layout permanently saved as Base Map.', 'success');
                },
                error: function() {
                    Swal.fire('Error', 'Failed to generate layout image.', 'error');
                }
            });
        }

        window.deleteSelectedShape = function() {
            if (!activeShape) return;
            Swal.fire({
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true
            }).then((result) => {
                if (result.isConfirmed) {
                    let editId = $('#v_edit_id').val();
                    if (editId) {
                        $.ajax({
                            url: `/api/v1/property-units/${editId}`,
                            type: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function() {
                                canvas.remove(activeShape);
                                $('#visualUnitForm').hide();
                                $('#instructionPanel').show();
                                if (typeof table !== 'undefined') table.ajax.reload(null, false);
                                Swal.fire('Deleted!', 'Permanently removed from database.',
                                    'success');
                            }
                        });
                    } else {
                        canvas.remove(activeShape);
                        $('#visualUnitForm').hide();
                        $('#instructionPanel').show();
                    }
                }
            });
        };

        window.openMasterMap = function() {
            $.get('/api/v1/phases', function(res) {
                if (!res.success || res.data.length === 0) return Swal.fire('Warning', 'No phases found.',
                    'warning');
                let phaseOptions = {};
                res.data.forEach(p => {
                    phaseOptions[p.id] = `${p.phase_name} (${p.branch ? p.branch.branch_name : 'HO'})`;
                });

                Swal.fire({
                    title: 'Select Phase for Viewer',
                    input: 'select',
                    inputOptions: phaseOptions,
                    inputPlaceholder: 'Search phase...',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-eye"></i> View Map'
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        $('#viewerPhaseName').text(' - Loading...');
                        $.get(`/api/v1/property-units/phase-map/${result.value}`, function(mapRes) {
                            if (!mapRes.success) return Swal.fire('Error', mapRes.message,
                                'error');
                            $('#viewerPhaseName').text(` (Phase ID: ${result.value})`);
                            $('#viewerRightPanel').hide();
                            $('#viewerPlaceholder').show();
                            $('#masterMapModal').modal('show');
                            setTimeout(() => {
                                initViewerCanvas(mapRes.map_url, mapRes.units);
                            }, 500);
                        });
                    }
                });
            });
        };

        // 🔥 NAYA FIX: Viewer Canvas centering 🔥
        window.initViewerCanvas = function(imageUrl, existingUnits) {
            let container = document.getElementById('viewerCanvasContainer');
            $(container).empty().append('<canvas id="viewerCanvas"></canvas>');

            viewerCanvas = new fabric.Canvas('viewerCanvas', {
                width: container.clientWidth,
                height: container.clientHeight,
                backgroundColor: '#e9ecef',
                selection: false,
                hoverCursor: 'pointer'
            });

            fabric.Image.fromURL(imageUrl, function(img) {
                let w = img.width;
                let h = img.height;

                if (imageUrl.includes('generated_layout_phase_')) {
                    let artboard = new fabric.Rect({
                        left: 0,
                        top: 0,
                        width: w,
                        height: h,
                        fill: '#ffffff',
                        stroke: '#000000',
                        strokeWidth: 4,
                        strokeUniform: true,
                        selectable: false,
                        evented: false,
                        id: 'artboard_bg',
                        objectCaching: false
                    });
                    viewerCanvas.add(artboard);
                } else {
                    img.set({
                        left: 0,
                        top: 0,
                        originX: 'left',
                        originY: 'top',
                        selectable: false,
                        evented: false,
                        id: 'artboard_bg',
                        objectCaching: false
                    });
                    viewerCanvas.add(img);
                    viewerCanvas.sendToBack(img);
                }

                let zoomX = container.clientWidth / (w + 100);
                let zoomY = container.clientHeight / (h + 100);
                let zoom = Math.min(zoomX, zoomY);

                viewerCanvas.setZoom(zoom);
                let vpt = viewerCanvas.viewportTransform;
                vpt[4] = (container.clientWidth - w * zoom) / 2;
                vpt[5] = (container.clientHeight - h * zoom) / 2;

                renderExistingUnits(existingUnits, viewerCanvas);
                attachCanvasEvents(viewerCanvas);
                viewerCanvas.renderAll();
            });
        }

        window.showViewerDetails = function(data) {
            $('#viewerPlaceholder').hide();
            $('#viewerRightPanel').show();
            $('#vu_name').text(data.unit_number);
            let displayType = data.entity_type.replace('_temp', '');
            $('#vu_type').text(displayType);

            let statusObj = $('#vu_status');
            statusObj.text(data.availability_status);
            if (data.availability_status === 'available') statusObj.removeClass().addClass(
                'mb-0 text-success fw-bold text-uppercase');
            else if (data.availability_status === 'booked') statusObj.removeClass().addClass(
                'mb-0 text-danger fw-bold text-uppercase');
            else statusObj.removeClass().addClass('mb-0 text-warning fw-bold text-uppercase');

            if (displayType === 'plot' || displayType === 'commercial') {
                $('#vu_plot_details').show();
                let areaStr = data.area ? `${data.area.area_name} ${data.area.measurement_unit}` : 'N/A';
                let catStr = (data.area && data.area.category) ? data.area.category.category_name : 'N/A';

                $('#vu_area').text(areaStr);
                $('#vu_category').text(catStr);
                $('#vu_east').text(data.boundaries?.east || 'N/A');
                $('#vu_west').text(data.boundaries?.west || 'N/A');
                $('#vu_north').text(data.boundaries?.north || 'N/A');
                $('#vu_south').text(data.boundaries?.south || 'N/A');
            } else {
                $('#vu_plot_details').hide();
            }
        }
    </script>
@endpush
