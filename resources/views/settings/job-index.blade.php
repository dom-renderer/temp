@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle, 'editor' => true])

@push('css')
<style>
    .escalation-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-radius: 12px;
        margin-bottom: 20px;
    }
    
    .escalation-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 20px;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        transform: translateY(-1px);
        transition: all 0.3s ease;
    }
    
    .escalation-level {
        border-left: 4px solid #667eea;
        background: #f8f9ff;
        margin-bottom: 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .escalation-level:hover {
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
    }
    
    .level-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 0.8rem;
        padding: 4px 12px;
        border-radius: 20px;
    }
    
    .priority-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    
    .priority-low { background-color: #4caf50; }
    .priority-medium { background-color: #ff9800; }
    .priority-high { background-color: #f44336; }
    .priority-critical { background-color: #9c27b0; }
    
    .template-preview {
        background: #f8f9fa;
        border: 1px dashed #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
    }
    
    .settings-section {
        margin-bottom: 30px;
    }
    
    .section-title {
        color: #495057;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e9ecef;
    }

    /* Job Inspection Styles */
    .inspection-item {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: move;
        transition: all 0.3s ease;
        position: relative;
    }

    .inspection-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }

    .inspection-item.dragging {
        opacity: 0.5;
        transform: rotate(5deg);
    }

    .inspection-item .remove-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .inspection-item .remove-btn:hover {
        background: #c82333;
    }

    .inspection-item .department-name {
        font-weight: 500;
        color: #495057;
        margin-right: 30px;
    }

    .inspection-item .order-number {
        background: #667eea;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        margin-right: 10px;
    }

    #container-for-job-inspection {
        min-height: 100px;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background: #f8f9fa;
    }

    #container-for-job-inspection.empty {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-style: italic;
    }

    .department-select-container {
        margin-bottom: 15px;
    }

    /* Job Inspection Styles */
    .inspection-item {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: move;
        transition: all 0.3s ease;
        position: relative;
    }

    .inspection-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }

    .inspection-item.dragging {
        opacity: 0.5;
        transform: rotate(5deg);
    }

    .inspection-item .remove-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .inspection-item .remove-btn:hover {
        background: #c82333;
    }

    .inspection-item .department-name {
        font-weight: 500;
        color: #495057;
        margin-right: 30px;
    }

    .inspection-item .order-number {
        background: #667eea;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        margin-right: 10px;
    }

    #container-for-job-inspection {
        min-height: 100px;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background: #f8f9fa;
    }

    #container-for-job-inspection.empty {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-style: italic;
    }

    .department-select-container {
        margin-bottom: 15px;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        @if(session('success'))
            <div class="p2 p-2 alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p2 p-2 alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
    <form method="POST" action="{{ route('job.settings-update') }}" enctype="multipart/form-data" id="escalationForm">
    @csrf

    <div class="row">
        @if ($errors->any())
        <div class="card">
            <div class="card-header">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif                
    </div>

    <div class="row mt-2">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="settings-section">
                        <h4 class="section-title">
                            <i class="fas fa-layer-group text-primary me-2"></i>
                            Escalation Levels
                        </h4>
                        
                        <div id="escalationLevels">
                            @if($escalations->count() > 0)
                                @foreach($escalations as $escalation)
                                    <input type="hidden" name="escalations[{{ $escalation->level }}][id]" value="{{ $escalation->id }}">
                                    <div class="escalation-level p-3" data-level="{{ $escalation->level }}">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">
                                                <span class="level-badge">Level {{ $escalation->level }}</span>
                                                <span class="priority-indicator priority-{{ strtolower($escalation->priority) }}"></span>
                                                Escalation Level {{ $escalation->level }}
                                            </h5>
                                            @if($loop->index > 0)
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-level">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="form-label">Trigger After</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" name="escalations[{{ $escalation->level }}][time]" value="{{ $escalation->time }}" min="1">
                                                    <select class="form-select" name="escalations[{{ $escalation->level }}][time_type]">
                                                        <option value="MINUTE" {{ $escalation->time_type === 'MINUTE' ? 'selected' : '' }}>Minutes</option>
                                                        <option value="HOUR" {{ $escalation->time_type === 'HOUR' ? 'selected' : '' }}>Hours</option>
                                                        <option value="DAY" {{ $escalation->time_type === 'DAY' ? 'selected' : '' }}>Days</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label class="form-label">Priority Level</label>
                                                <select class="form-select priority-select" name="escalations[{{ $escalation->level }}][priority]">
                                                    <option value="LOW" {{ $escalation->priority === 'LOW' ? 'selected' : '' }}>Low Priority</option>
                                                    <option value="MEDIUM" {{ $escalation->priority === 'MEDIUM' ? 'selected' : '' }}>Medium Priority</option>
                                                    <option value="HIGH" {{ $escalation->priority === 'HIGH' ? 'selected' : '' }}>High Priority</option>
                                                    <option value="CRITICAL" {{ $escalation->priority === 'CRITICAL' ? 'selected' : '' }}>Critical Priority</option>
                                                </select>
                                            </div>
                                        
                                            <div class="col-5">
                                                <label class="form-label">Email Template</label>
                                                <select class="template-select" name="escalations[{{ $escalation->level }}][template_id]" required>
                                                    <option value="{{ $escalation->template_id }}" selected>{{ $escalation->template->title ?? 'Select Template' }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <label class="form-label">Departments</label>
                                                <div class="input-group">
                                                    <select name="escalations[{{ $escalation->level }}][departments][]" class="dept-s2" multiple>
                                                        @foreach($escalation->departments as $deptId)
                                                            <option value="{{ $deptId }}" selected>{{ \App\Models\Department::find($deptId)->name ?? '' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                @endforeach
                            @else
                                <!-- Level 1 -->
                                <div class="escalation-level p-3" data-level="1">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <span class="level-badge">Level 1</span>
                                            <span class="priority-indicator priority-low"></span>
                                            Escalation Level 1
                                        </h5>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label">Trigger After</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="escalations[1][time]" value="15" min="1">
                                                <select class="form-select" name="escalations[1][time_type]">
                                                    <option value="MINUTE" selected>Minutes</option>
                                                    <option value="HOUR">Hours</option>
                                                    <option value="DAY">Days</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">Priority Level</label>
                                            <select class="form-select priority-select" name="escalations[1][priority]">
                                                <option value="LOW" selected>Low Priority</option>
                                                <option value="MEDIUM">Medium Priority</option>
                                                <option value="HIGH">High Priority</option>
                                                <option value="CRITICAL">Critical Priority</option>
                                            </select>
                                        </div>
                                    
                                        <div class="col-5">
                                            <label class="form-label">Email Template</label>
                                            <select class="template-select" name="escalations[1][template_id]" required>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <label class="form-label">Departments</label>
                                            <div class="input-group">
                                                <select name="escalations[1][departments][]" class="dept-s2" multiple></select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            @endif
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary" id="addLevel">
                            <i class="fas fa-plus me-2"></i>Add Escalation Level
                        </button>
                    </div>
                </div>
            </div>
        </div> 
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="section-title">
                        <i class="fas fa-clipboard-check text-primary me-2"></i>
                        Job Inspection Levels
                    </h4>

                    <div class="department-select-container">
                        <label class="form-label">Select Department</label>
                        <div class="input-group">
                            <select id="all-departments" class="form-select">
                                <option value="">Choose a department...</option>
                            </select>
                            <button type="button" class="btn btn-primary" id="addInspectionDepartment">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>

                    <div id="container-for-job-inspection" class="{{ $jobInspectionOrders->count() == 0 ? 'empty' : '' }}">
                        @if($jobInspectionOrders->count() == 0)
                            <span>No departments added yet. Select a department and click Add to get started.</span>
                        @else
                            @foreach($jobInspectionOrders as $order)
                                <div class="inspection-item" data-id="{{ $order->id }}" data-department-id="{{ $order->department_id }}">
                                    <input type="hidden" name="job_inspection_orders[{{ $order->ordering }}][id]" value="{{ $order->id }}">
                                    <input type="hidden" name="job_inspection_orders[{{ $order->ordering }}][department_id]" value="{{ $order->department_id }}">
                                    <input type="hidden" name="job_inspection_orders[{{ $order->ordering }}][ordering]" value="{{ $order->ordering }}">
                                    <span class="order-number">{{ $order->ordering }}</span>
                                    <span class="department-name">{{ $order->department->name }}</span>
                                    <button type="button" class="remove-btn" onclick="removeInspectionDepartment(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 mt-2">
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Job Settings
                    </button>
                </div>
            </div>
        </div>
    </div>    

    </form>
</div>
@endsection

@push('css')
@endpush

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script>
        $(document).ready(function() {
            let levelCounter = {{ $escalations->count() > 0 ? $escalations->max('level') : 1 }};
            let inspectionOrderCounter = {{ $jobInspectionOrders->count() > 0 ? $jobInspectionOrders->max('ordering') : 0 }};
            let addedDepartments = new Set();
            
            @foreach($jobInspectionOrders as $order)
                addedDepartments.add({{ $order->department_id }});
            @endforeach
            
            $('.dept-s2, #all-departments').select2({
                allowClear: true,
                placeholder: 'Select departments',
                width: '100%',
                ajax: {
                    url: "{{ route('department-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}"
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;

                        return {
                            results: $.map(data.items, function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                };
                            }),
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }

                    var $result = $('<span></span>');
                    $result.text(data.text);
                    return $result;
                }
            });

            $('.template-select').select2({
                allowClear: true,
                placeholder: 'Select template',
                width: '100%',
                ajax: {
                    url: "{{ route('notification-template-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}"
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;

                        return {
                            results: $.map(data.items, function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                };
                            }),
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }

                    var $result = $('<span></span>');
                    $result.text(data.text);
                    return $result;
                }
            });

            $('#addLevel').click(function() {
                levelCounter++;
                createEscalationLevel(levelCounter);
            });
            
            $(document).on('click', '.remove-level', function() {
                if ($('.escalation-level').length > 1) {
                    $(this).closest('.escalation-level').remove();
                } else {
                    alert('At least one escalation level is required');
                }
            });
            
            $(document).on('change', '.priority-select', function() {
                const priority = $(this).val().toLowerCase();
                const indicator = $(this).closest('.escalation-level').find('.priority-indicator');
                indicator.removeClass('priority-low priority-medium priority-high priority-critical');
                indicator.addClass('priority-' + priority);
            });

            $('#addInspectionDepartment').click(function() {
                const departmentSelect = $('#all-departments');
                const departmentId = departmentSelect.val();
                const departmentText = departmentSelect.find('option:selected').text();

                if (!departmentId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Department Selected',
                        text: 'Please select a department first.'
                    });
                    return;
                }

                if (addedDepartments.has(parseInt(departmentId))) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Department Already Added',
                        text: 'This department is already in the inspection list.'
                    });
                    return;
                }

                inspectionOrderCounter++;
                addedDepartments.add(parseInt(departmentId));

                const inspectionItem = `
                    <div class="inspection-item" data-department-id="${departmentId}">
                        <input type="hidden" name="job_inspection_orders[${inspectionOrderCounter}][department_id]" value="${departmentId}">
                        <input type="hidden" name="job_inspection_orders[${inspectionOrderCounter}][ordering]" value="${inspectionOrderCounter}">
                        <span class="order-number">${inspectionOrderCounter}</span>
                        <span class="department-name">${departmentText}</span>
                        <button type="button" class="remove-btn" onclick="removeInspectionDepartment(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;

                $('#container-for-job-inspection').append(inspectionItem);
                $('#container-for-job-inspection').removeClass('empty');
                departmentSelect.val('').trigger('change');

                updateOrderNumbers();
            });

            const container = document.getElementById('container-for-job-inspection');
            if (container) {
                new Sortable(container, {
                    animation: 150,
                    ghostClass: 'dragging',
                    onEnd: function() {
                        updateOrderNumbers();
                    }
                });
            }

            $('#escalationForm').on('submit', function(e) {
                e.preventDefault();
                
                let isValid = true;
                let errorMessage = '';
                
                $('.template-select').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        errorMessage = 'Please select an email template for all escalation levels.';
                        return false;
                    }
                });
                
                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: errorMessage
                    });
                    return;
                }
                
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
                
                this.submit();
            });
        });
        
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
        
        function removeInspectionDepartment(button) {
            const item = $(button).closest('.inspection-item');
            const departmentId = item.data('department-id');
            
            Swal.fire({
                title: 'Remove Department?',
                text: 'Are you sure you want to remove this department from the inspection list?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {

                    addedDepartments.delete(parseInt(departmentId));
                    
                    item.remove();
                    
                    updateOrderNumbers();
                    
                    if ($('#container-for-job-inspection .inspection-item').length === 0) {
                        $('#container-for-job-inspection').addClass('empty').html('<span>No departments added yet. Select a department and click Add to get started.</span>');
                    }
                }
            });
        }
        
        function updateOrderNumbers() {
            $('#container-for-job-inspection .inspection-item').each(function(index) {
                const orderNumber = index + 1;
                $(this).find('.order-number').text(orderNumber);
                $(this).find('input[name*="[ordering]"]').val(orderNumber);
                
                const orderingInput = $(this).find('input[name*="[ordering]"]');
                const departmentInput = $(this).find('input[name*="[department_id]"]');
                const idInput = $(this).find('input[name*="[id]"]');
                
                const newName = orderingInput.attr('name').replace(/\[\d+\]/, `[${orderNumber}]`);
                orderingInput.attr('name', newName);
                
                const newDeptName = departmentInput.attr('name').replace(/\[\d+\]/, `[${orderNumber}]`);
                departmentInput.attr('name', newDeptName);
                
                if (idInput.length > 0) {
                    const newIdName = idInput.attr('name').replace(/\[\d+\]/, `[${orderNumber}]`);
                    idInput.attr('name', newIdName);
                }
            });
        }
                
        function createEscalationLevel(level) {
            const priorities = ['low', 'medium', 'high', 'critical'];
            const priorityNames = ['Low Priority', 'Medium Priority', 'High Priority', 'Critical Priority'];
            const priority = priorities[Math.min(level - 1, 3)];
            const priorityName = priorityNames[Math.min(level - 1, 3)];
            
            const templateString =  `
                <div class="escalation-level p-3" data-level="${level}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <span class="level-badge">Level ${level}</span>
                            <span class="priority-indicator priority-${priority}"></span>
                            Escalation Level ${level}
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-level">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Trigger After</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="escalations[${level}][time]" value="${level * 30}" min="1">
                                <select class="form-select" name="escalations[${level}][time_type]">
                                    <option value="MINUTE" ${level === 1 ? 'selected' : ''}>Minutes</option>
                                    <option value="HOUR" ${level > 1 ? 'selected' : ''}>Hours</option>
                                    <option value="DAY">Days</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Priority Level</label>
                            <select class="form-select priority-select" name="escalations[${level}][priority]">
                                <option value="LOW" ${priority === 'low' ? 'selected' : ''}>Low Priority</option>
                                <option value="MEDIUM" ${priority === 'medium' ? 'selected' : ''}>Medium Priority</option>
                                <option value="HIGH" ${priority === 'high' ? 'selected' : ''}>High Priority</option>
                                <option value="CRITICAL" ${priority === 'critical' ? 'selected' : ''}>Critical Priority</option>
                            </select>
                        </div>

                        <div class="col-5">
                            <label class="form-label">Email Template</label>
                            <select class="template-select" name="escalations[${level}][template_id]" required>
                            </select>
                        </div>
                        
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <label class="form-label">Departments</label>
                            <div class="input-group">
                                <select name="escalations[${level}][departments][]" class="dept-s2" multiple></select>
                            </div>
                        </div>
                        
                    </div>
                </div>
            `;

            $('#escalationLevels').append(templateString);

            $(`select[name="escalations[${level}][departments][]"]`).select2({
                allowClear: true,
                placeholder: 'Select departments',
                width: '100%',
                ajax: {
                    url: "{{ route('department-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}"
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;

                        return {
                            results: $.map(data.items, function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                };
                            }),
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }

                    var $result = $('<span></span>');
                    $result.text(data.text);
                    return $result;
                }
            });

            $(`select[name="escalations[${level}][template_id]"]`).select2({
                allowClear: true,
                placeholder: 'Select template',
                width: '100%',
                ajax: {
                    url: "{{ route('notification-template-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}"
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;

                        return {
                            results: $.map(data.items, function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                };
                            }),
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }

                    var $result = $('<span></span>');
                    $result.text(data.text);
                    return $result;
                }
            });
        }
    </script>
@endpush