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
    
    .recipient-tag {
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 8px;
        border-radius: 15px;
        font-size: 0.85rem;
        margin: 2px;
        display: inline-block;
    }
    
    .recipient-tag .remove-tag {
        margin-left: 5px;
        cursor: pointer;
        color: #f44336;
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
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        @if(session('success'))
            <div class="p2 p-2 alert alert-success">{{ session('success') }}</div>
        @endif
    </div>
    <form method="POST" action="{{ route('job.settings-update') }}" enctype="multipart/form-data">
    @csrf

    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
























                            <div class="settings-section">
                                <h4 class="section-title">
                                    <i class="fas fa-layer-group text-primary me-2"></i>
                                    Escalation Levels
                                </h4>
                                
                                <div id="escalationLevels">
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
                                                    <input type="number" class="form-control" value="15" min="1">
                                                    <select class="form-select">
                                                        <option value="minutes">Minutes</option>
                                                        <option value="hours">Hours</option>
                                                        <option value="days">Days</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label class="form-label">Priority Level</label>
                                                <select class="form-select priority-select">
                                                    <option value="low" selected>Low Priority</option>
                                                    <option value="medium">Medium Priority</option>
                                                    <option value="high">High Priority</option>
                                                    <option value="critical">Critical Priority</option>
                                                </select>
                                            </div>
                                        
                                            <div class="col-5">
                                                <label class="form-label">Email Template</label>
                                                <select class="template-select" id="template-0" required>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-md-5">
                                                <label class="form-label">Recipients</label>
                                                <div class="input-group">
                                                    <input type="email" class="form-control recipient-input" placeholder="Enter email address">
                                                    <button type="button" class="btn btn-outline-primary add-recipient">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <div class="recipients-list mt-2"></div>
                                            </div>

                                            <div class="col-md-2">
                                                <p style="position: relative;top: 35px;left: 40px;">
                                                    OR
                                                </p>
                                            </div>

                                            <div class="col-md-5">
                                                <label class="form-label">Departments</label>
                                                <div class="input-group">
                                                    <select id="departments-0" class="dept-s2"></select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-outline-primary" id="addLevel">
                                    <i class="fas fa-plus me-2"></i>Add Escalation Level
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
    <script>
        $(document).ready(function() {
            let levelCounter = 1;
            
            $('#departments-0').select2({
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

            $('#template-0').select2({
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

            $(document).on('click', '.add-recipient', function() {
                const input = $(this).siblings('.recipient-input');
                const email = input.val().trim();
                const recipientsList = $(this).closest('.col-md-5').find('.recipients-list');
                let emailIsUniqe = true;
                
                if (email && validateEmail(email)) {

                    $(this).parent().parent().find('.recipient-tag').each(function() {
                        if ($(this).text().replace('×', '').trim().toLowerCase() == email.toLowerCase()) {
                            emailIsUniqe = false;
                            input.val('');
                        }
                    });

                    if (emailIsUniqe) {
                        const tag = `<span class="recipient-tag">${email}<span class="remove-tag" onclick="removeRecipient(this)">×</span></span>`;
                        recipientsList.append(tag);
                        input.val('');
                    }
                } else {
                    alert('Please enter a valid email address');
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
                const priority = $(this).val();
                const indicator = $(this).closest('.escalation-level').find('.priority-indicator');
                indicator.removeClass('priority-low priority-medium priority-high priority-critical');
                indicator.addClass('priority-' + priority);
            });
            
            $('#testEscalation').click(function() {
                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Testing...');
                
                setTimeout(function() {
                    btn.prop('disabled', false).html('<i class="fas fa-flask me-2"></i>Test Configuration');
                    alert('Test email sent successfully! Check your inbox for the escalation notification.');
                }, 2000);
            });
            
        });
        
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
        
        function removeRecipient(element) {
            $(element).parent().remove();
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
                                <input type="number" class="form-control" value="${level * 30}" min="1">
                                <select class="form-select">
                                    <option value="minutes" ${level === 1 ? 'selected' : ''}>Minutes</option>
                                    <option value="hours" ${level > 1 ? 'selected' : ''}>Hours</option>
                                    <option value="days">Days</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Priority Level</label>
                            <select class="form-select priority-select">
                                <option value="low" ${priority === 'low' ? 'selected' : ''}>Low Priority</option>
                                <option value="medium" ${priority === 'medium' ? 'selected' : ''}>Medium Priority</option>
                                <option value="high" ${priority === 'high' ? 'selected' : ''}>High Priority</option>
                                <option value="critical" ${priority === 'critical' ? 'selected' : ''}>Critical Priority</option>
                            </select>
                        </div>

                        <div class="col-5">
                            <label class="form-label">Email Template</label>
                            <select class="template-select" id="template-${level}" required>
                            </select>
                        </div>
                        
                    </div>
                    
                    <div class="row mt-4">

                        <div class="col-md-5">
                            <label class="form-label">Recipients</label>
                            <div class="input-group">
                                <input type="email" class="form-control recipient-input" placeholder="Enter email address">
                                <button type="button" class="btn btn-outline-primary add-recipient">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="recipients-list mt-2"></div>
                        </div>

                        <div class="col-md-2">
                            <p style="position: relative;top: 35px;left: 40px;">
                                OR
                            </p>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Departments</label>
                            <div class="input-group">
                                <select id="departments-${level}" class="dept-s2"></select>
                            </div>
                        </div>
                        
                    </div>
                </div>
            `;

            $('#escalationLevels').append(templateString);

            $(`#departments-${level}`).select2({
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

            $(`#template-${level}`).select2({
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
                
        function collectFormData() {
            const formData = {
                policyName: $('#policyName').val(),
                status: $('#policyStatus').val(),
                businessHours: $('#businessHours').val(),
                timeZone: $('#timeZone').val(),
                subjectTemplate: $('#subjectTemplate').val(),
                bodyTemplate: $('#bodyTemplate').val(),
                fromEmail: $('#fromEmail').val(),
                levels: []
            };
            
            $('.escalation-level').each(function() {
                const level = {
                    level: $(this).data('level'),
                    triggerValue: $(this).find('input[type="number"]').val(),
                    triggerUnit: $(this).find('.input-group select').val(),
                    priority: $(this).find('.priority-select').val(),
                    template: $(this).find('.template-select').val(),
                    recipients: []
                };
                
                $(this).find('.recipient-tag').each(function() {
                    const email = $(this).text().replace('×', '').trim();
                    level.recipients.push(email);
                });
                
                formData.levels.push(level);
            });
            
            return formData;
        }
    </script>
@endpush