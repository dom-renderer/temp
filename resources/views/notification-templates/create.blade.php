@extends('layouts.app',['title' => $title, 'subTitle' => $subTitle, 'editor' => true, 'select2' => true])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add New Notification Template</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('notification-templates.store') }}" method="POST" id="notificationTemplateForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Notification Type <span class="text-danger">*</span></label>
                                <select name="type[]" id="type" class="form-select select2" multiple required>
                                    @foreach($notificationTypes as $typeSlug => $type)
                                        <option value="{{ $typeSlug }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="type-error"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    <option value="ACTIVE">Active</option>
                                    <option value="INACTIVE">Inactive</option>
                                </select>
                                <div class="invalid-feedback" id="status-error"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                        <div class="invalid-feedback" id="title-error"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Available Variables</label>
                        <div class="row">
                            @foreach($availableVariables as $variable)
                                <div class="col-md-3 mb-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm variable-btn" data-variable="{{ $variable }}">
                                        {{ $variable }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Click on any variable to copy it to clipboard</small>
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label">Body Content <span class="text-danger">*</span></label>
                        <input type="hidden" name="body" id="body">
                        <div id="body_editor" style="height: 250px;"></div>
                        <div class="invalid-feedback" id="body-error"></div>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('notification-templates.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Select options',
            allowClear: true
        });

        const quill = new Quill('#body_editor', {
            theme: 'snow'
        });

        $(document).on('click', '.variable-btn', function() {
            const variable = $(this).data('variable');
            
            navigator.clipboard.writeText(variable).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Variable Copied!',
                    text: variable + ' has been copied to clipboard',
                    timer: 1500,
                    showConfirmButton: false
                });
            }).catch(function() {
                const textArea = document.createElement('textarea');
                textArea.value = variable;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Variable Copied!',
                    text: variable + ' has been copied to clipboard',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        });

        $('#notificationTemplateForm').validate({
            rules: {
                'type[]': {
                    required: true,
                    minlength: 1
                },
                title: {
                    required: true,
                    minlength: 3,
                    maxlength: 255
                },
                body: {
                    required: true,
                    minlength: 10
                },
                status: {
                    required: true
                }
            },
            messages: {
                'type[]': {
                    required: 'Please select at least one notification type',
                    minlength: 'Please select at least one notification type'
                },
                title: {
                    required: 'Please enter a title',
                    minlength: 'Title must be at least 3 characters long',
                    maxlength: 'Title cannot exceed 255 characters'
                },
                body: {
                    required: 'Please enter body content',
                    minlength: 'Body content must be at least 10 characters long'
                },
                status: {
                    required: 'Please select a status'
                }
            },
            errorElement: 'span',
            errorClass: 'invalid-feedback',
            highlight: function(element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            },
            errorPlacement: function(error, element) {
                if (element.attr('name') === 'type[]') {
                    error.insertAfter('#type');
                } else if (element.attr('name') === 'body') {
                    error.insertAfter('#body');
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                const content = quill.root.innerHTML.trim();
                $('#body').val(content);

                form.submit();
            }
        });
    });
</script>
@endpush 