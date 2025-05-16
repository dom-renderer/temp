@extends('layouts.app-master')

@push('css')
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
    <style>
        .attachment-preview {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .progress {
            height: 20px;
            margin-bottom: 15px;
        }

        .dropzone {
            border: 2px dashed #0087F7;
            border-radius: 5px;
            background: #F3F4F6;
            min-height: 150px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .modal-fullscreen-padded .modal-dialog {
            position: fixed;
            top: 30px;
            left: 30px;
            right: 30px;
            bottom: 30px;
            margin: 0;
            max-width: none;
            width: auto;
            height: auto;
        }

        .modal-fullscreen-padded .modal-content {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .modal-fullscreen-padded .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
        }
    </style>
@endpush

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('contents.update', $content->id) }}" method="POST" enctype="multipart/form-data" id="content-form">
                @csrf
                @method('PUT')

                <div class="form-group mb-3">
                    <label for="topic_id">Topic <span class="text-danger">*</span></label>
                    <select name="topic_id" id="topic_id" class="form-control @error('topic_id') is-invalid @enderror"
                        required>
                        <option value="{{ $content->topic_id }}" selected>{{ $content->topic->name }}</option>
                    </select>
                    @error('topic_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="tags">Tags <span class="text-danger">*</span></label>
                    <select name="tags[]" id="tags" class="form-control @error('tags') is-invalid @enderror" required
                        multiple>
                        @foreach($content->tags as $tag)
                            @if(isset($tag->tag->id))
                                <option value="{{ $tag->tag->title }}" selected>{{ $tag->tag->title }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('tags')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="title">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title"
                        class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $content->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="description" rows="5"
                        class="form-control @error('description') is-invalid @enderror">{{ old('description', $content->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="1" {{ old('status', $content->status) == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $content->status) == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="maker_role" class="form-label"> Role <span class="text-danger"> * </span> </label>
                    <select name="roles[]" id="maker_role" multiple required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" 
                                {{ in_array($role->id, $selectedRoles) ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>

                    @if ($errors->has('maker_role'))
                        <span class="text-danger text-left">{{ $errors->first('maker_role') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="maker_employee" class="form-label"> Employee </label>
                    <select name="employees[]" id="maker_employee" multiple>
                        @foreach ($selectedEmployees as $employee)
                            <option value="{{ $employee->id }}" selected>{{ $employee->name }}</option>
                        @endforeach
                    </select>

                    @if ($errors->has('maker_employee'))
                        <span class="text-danger text-left">{{ $errors->first('maker_employee') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <div class="form-group">
                        <input type="radio" name="assination_type" id="type1" value="1" 
                            {{ $permissionMatrix['type'] == 1 ? 'checked' : '' }}>
                        <label for="type1" class="form-label"> Visible to all employees of selected roles </label>
                    </div>

                    <div class="form-group">
                        <input type="radio" name="assination_type" id="type2" value="2"
                            {{ $permissionMatrix['type'] == 2 ? 'checked' : '' }}>
                        <label for="type2" class="form-label"> Visible to only selected employees of selected roles </label>
                    </div>

                    <div class="form-group">
                        <input type="radio" name="assination_type" id="type3" value="3"
                            {{ $permissionMatrix['type'] == 3 ? 'checked' : '' }}>
                        <label for="type3" class="form-label"> Visible to only employees except selected users of selected roles </label>
                    </div>
                </div>

                <hr>

                <h4>Content Attachments <span class="text-danger">*</span></h4>
                <p class="text-muted">Please add at least one attachment (image, video or document)</p>

                <div id="attachments-container">
                    @foreach($content->attachments as $index => $attachment)
                        <div class="attachment-preview" id="attachment-{{ $index }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5>
                                    @if($attachment->type == 'image')
                                        <i class="fa fa-image"></i>
                                    @elseif($attachment->type == 'video')
                                        <i class="fa fa-video"></i>
                                    @else
                                        <i class="fa fa-file"></i>
                                    @endif
                                    {{ ucfirst($attachment->type) }}
                                </h5>
                                <button type="button" class="btn btn-danger btn-sm remove-attachment" data-id="{{ $index }}">
                                    <i class="fa fa-times"></i> Remove
                                </button>
                            </div>
                            <p><strong>File:</strong> <a href="{{ asset("storage/content_attachments/" . basename($attachment->path)) }}"> {{ basename($attachment->path) }} </a> </p>
                            <p><strong>Description:</strong> {!! $attachment->description ?? 'N/A' !!}</p>
                            
                            <input type="hidden" name="attachments[{{ $index }}][id]" value="{{ $attachment->id }}">
                            <input type="hidden" name="attachments[{{ $index }}][type]" value="{{ $attachment->type }}">
                            <input type="hidden" name="attachments[{{ $index }}][description]" value="{{ $attachment->description }}">
                            <input type="hidden" name="attachments[{{ $index }}][path]" value="{{ $attachment->path }}">
                        </div>
                    @endforeach
                </div>

                <div class="text-center mb-4">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attachmentModal">
                        <i class="fa fa-plus"></i> Add Attachment
                    </button>
                </div>

                <div class="form-group text-center">
                    <button type="submit" class="btn btn-success" id="submit-btn">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Attachment Modal -->
    <div class="modal fade modal-fullscreen-padded" id="attachmentModal" tabindex="-1" aria-labelledby="attachmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attachmentModalLabel">Add Attachment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="attachment-type">Type <span class="text-danger">*</span></label>
                        <select id="attachment-type" class="form-control" required>
                            <option value="">-- Select Type --</option>
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                            <option value="document">Document</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="attachment-file">File <span class="text-danger">*</span></label>
                        <div id="dropzone-upload" class="dropzone"></div>
                        <div class="progress d-none" id="upload-progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0"
                                aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="attachment-description">Description</label>
                        <textarea id="attachment-description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="add-attachment-btn">Add Attachment</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        Dropzone.autoDiscover = false;

        document.addEventListener('DOMContentLoaded', function() {
            let attachmentCounter = {{ count($content->attachments) }};
            let uploadedFiles = {};
            let fileUploadCompleted = false;
            let currentFileType = '';
            let myDropzone;

            const acceptedTypes = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                'video/mp4', 'video/avi',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv'
            ];

            const fileTypeExtensions = {
                image: ".jpeg,.jpg,.png,.gif",
                video: ".mp4,.avi,.mov,.wmv,.flv",
                document: ".pdf,.docx,.xlsx,.csv,.txt"
            };

            myDropzone = new Dropzone("#dropzone-upload", {
                url: "{{ route('contents.upload-attachment') }}",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                autoProcessQueue: false,
                maxFiles: 1,
                chunking: true,
                chunkSize: 2000000,
                parallelChunkUploads: true,
                addRemoveLinks: true,
                dictRemoveFile: 'Remove',
                dictDefaultMessage: "Click or drag a file here to upload",
                acceptedFiles: ".jpeg,.jpg,.png,.gif,.mp4,.avi,.mov,.wmv,.flv,.pdf,.docx,.xlsx,.csv,.txt",
                accept: function(file, done) {
                    if (acceptedTypes.includes(file.type)) {
                        done();
                    } else {
                        done("This file type is not allowed.");
                        this.removeFile(file);
                    }
                },
                init: function() {
                    var dz = this;

                    this.on("sending", function(file, xhr, formData) {
                        if ($('body').find('.LoaderSec').hasClass('d-none')) {
                            $('body').find('.LoaderSec').removeClass('d-none');
                        }

                        formData.append("type", currentFileType);
                        $('#upload-progress').removeClass('d-none');
                    });

                    this.on("uploadprogress", function(file, progress) {
                        if (!acceptedTypes.includes(file.type)) {
                            this.removeFile(file);
                            return false;
                        }

                        if ($('body').find('.LoaderSec').hasClass('d-none')) {
                            $('body').find('.LoaderSec').removeClass('d-none');
                        }

                        $('.progress-bar').css('width', progress + '%');
                        $('.progress-bar').text(Math.round(progress) + '%');
                    });

                    this.on("maxfilesexceeded", function(file) {
                        this.removeAllFiles();
                        this.addFile(file);
                    });

                    this.on("success", function(file, response) {
                        fileUploadCompleted = false;

                        const filename = response.original_name;

                        $('.progress-bar').css('width', '0%').text('Compressing 0%');
                        $('#upload-progress').removeClass('d-none').addClass('bg-info');

                        document.getElementById('add-attachment-btn').disabled = false;
                        if (!$('body').find('.LoaderSec').hasClass('d-none')) {
                            $('body').find('.LoaderSec').addClass('d-none');
                        }
                        
                        fileUploadCompleted = true;
                        uploadedFiles = {
                            path: response.path,
                            originalName: response.original_name
                        };
                    });


                    this.on("error", function(file, errorMessage) {
                        this.removeFile(file);
                        console.error(errorMessage);
                        if (!$('body').find('.LoaderSec').hasClass('d-none')) {
                            $('body').find('.LoaderSec').addClass('d-none');
                        }
                        alert("Error uploading file: " + errorMessage);
                    });

                    this.on("complete", function(file) {
                        if (this.getUploadingFiles().length === 0 && this.getQueuedFiles()
                            .length === 0) {
                            $('.progress-bar').css('width', '100%');
                            $('.progress-bar').text('100%');
                        }
                    });

                    this.on("addedfile", function(file) {
                        if ($('body').find('.LoaderSec').hasClass('d-none')) {
                            $('body').find('.LoaderSec').removeClass('d-none');
                        }

                        if (file.status === Dropzone.SUCCESS || file.status === Dropzone.ERROR) {
                            myDropzone.removeFile(file);
                        }

                        if (currentFileType === '') {
                            alert("Please select attachment type first!");
                            this.removeAllFiles(true);

                        if (!$('body').find('.LoaderSec').hasClass('d-none')) {
                            $('body').find('.LoaderSec').addClass('d-none');
                        }

                            return false;
                        }

                        document.getElementById('add-attachment-btn').disabled = true;
                        this.processFile(file);
                    });
                }
            });

            document.getElementById('attachment-type').addEventListener('change', function() {
                const selectedType = this.value;
                const acceptedExtensions = fileTypeExtensions[selectedType] || '';
                currentFileType = selectedType;

                myDropzone.options.acceptedFiles = acceptedExtensions;

                const fileInput = myDropzone.hiddenFileInput;
                if (fileInput) {
                    fileInput.setAttribute('accept', acceptedExtensions);
                }
            });

            document.querySelector("#dropzone-upload").addEventListener("drop", function(e) {
                if (currentFileType === '') {
                    alert("Please select attachment type first!");
                    myDropzone.removeAllFiles(true);
                    return false;
                }

                if (myDropzone.files.length > 0) {
                    document.getElementById('add-attachment-btn').disabled = true;
                    myDropzone.processQueue();
                }
            });

            document.getElementById('add-attachment-btn').addEventListener('click', function() {
                if (!fileUploadCompleted) {
                    alert("Please upload a file first!");
                    return;
                }

                const type = document.getElementById('attachment-type').value;
                const description = document.getElementById('attachment-description').value;

                if (!type) {
                    alert("Please select attachment type!");
                    return;
                }

                const attachmentDiv = document.createElement('div');
                attachmentDiv.className = 'attachment-preview';
                attachmentDiv.id = 'attachment-' + attachmentCounter;

                let fileTypeIcon = '';
                if (type === 'image') {
                    fileTypeIcon = '<i class="fa fa-image"></i>';
                } else if (type === 'video') {
                    fileTypeIcon = '<i class="fa fa-video"></i>';
                } else {
                    fileTypeIcon = '<i class="fa fa-file"></i>';
                }

                attachmentDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5>${fileTypeIcon} ${type.charAt(0).toUpperCase() + type.slice(1)}</h5>
                    <button type="button" class="btn btn-danger btn-sm remove-attachment" data-id="${attachmentCounter}">
                        <i class="fa fa-times"></i> Remove
                    </button>
                </div>
                <p><strong>File:</strong> ${uploadedFiles.originalName}</p>
                <p><strong>Description:</strong> ${description || 'N/A'}</p>
                
                <input type="hidden" name="attachments[${attachmentCounter}][type]" value="${type}">
                <input type="hidden" name="attachments[${attachmentCounter}][description]" value="${description}">
                <input type="hidden" name="attachments[${attachmentCounter}][file]" value="${uploadedFiles.path}">
                <input type="hidden" name="attachments[${attachmentCounter}][path]" value="${uploadedFiles.path}">
            `;

                document.getElementById('attachments-container').appendChild(attachmentDiv);

                myDropzone.removeAllFiles(true);
                document.getElementById('attachment-type').value = '';
                document.getElementById('attachment-description').value = '';
                $('#upload-progress').addClass('d-none');
                $('.progress-bar').css('width', '0%');
                $('.progress-bar').text('0%');

                fileUploadCompleted = false;
                uploadedFiles = {};
                currentFileType = '';

                $('#attachmentModal').modal('hide');

                attachmentCounter++;
            });

            $(document).on('click', '.remove-attachment', function() {
                const id = $(this).data('id');
                $('#attachment-' + id).remove();
            });

            document.getElementById('content-form').addEventListener('submit', function(e) {
                const attachmentsContainer = document.getElementById('attachments-container');
                if (attachmentsContainer.children.length === 0) {
                    e.preventDefault();
                    alert('Please add at least one attachment!');
                }
            });

            $(document).ready(function () {
                
                $( "#attachments-container" ).sortable();
                
                $(`#description`).summernote({
                    height: 200,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'underline', 'clear']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ]
                });

                $(`#attachment-description`).summernote({
                    height: 260,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'underline', 'clear']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ]
                });

                $('#tags').select2({
                    allowClear: true,
                    tags: true,
                    placeholder: "Select tags",
                    width: '100%',
                    createTag: function(params) {
                        let term = $.trim(params.term);
                        if (term === '') return null;
                        return {
                            id: term,
                            text: term,
                            newTag: true
                        };
                    },
                    ajax: {
                        url: "{{ route('tag-select2') }}",
                        type: "POST",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                searchQuery: params.term,
                                page: params.page || 1,
                                _token: "{{ csrf_token() }}",
                                onlyactive: 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;

                            let results = $.map(data.items, function(item) {
                                return {
                                    id: item.text,
                                    text: item.text
                                };
                            });

                            return {
                                results: results,
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        },
                        cache: true
                    },
                    templateResult: function(data) {
                        if (data.loading) return data.text;
                        return $('<span>' + data.text + '</span>');
                    },
                    templateSelection: function(data) {
                        return data.text;
                    }
                });

                $('#attachmentModal').on('hidden.bs.modal', function (e) {
                    if (e.namespace == 'bs.modal') {
                        $('#attachment-description').summernote('code', '');
                    }
                });

                $('#topic_id').select2({
                    allowClear: true,
                    placeholder: "Select a topic",
                    width: '100%',
                    ajax: {
                        url: "{{ route('topics-select2') }}",
                        type: "POST",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                searchQuery: params.term,
                                page: params.page || 1,
                                _token: "{{ csrf_token() }}",
                                onlyactive: 1
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

                $('#maker_role').select2({
                    placeholder: 'Select Role',
                    allowClear: true,
                    width: '100%',
                    theme: 'classic'
                }).on('change', function () {
                    $('#maker_employee').val(null).trigger('change');
                });

                $('#maker_employee').select2({
                    placeholder: 'Select Employee',
                    allowClear: true,
                    width: '100%',
                    theme: 'classic',
                    ajax: {
                        url: "{{ route('users-list') }}",
                        type: "POST",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                searchQuery: params.term,
                                page: params.page || 1,
                                _token: "{{ csrf_token() }}",
                                ignoreDesignation: 1,
                                roles: function () {
                                    return $('#maker_role').val();
                                }
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
            });
        });
    </script>
@endpush
