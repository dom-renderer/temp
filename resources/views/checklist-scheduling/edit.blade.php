@extends('layouts.app-master')

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/css/custom-select-style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/jquery.datetimepicker.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="bg-light p-4 rounded">
        <form method="POST" action="{{ route('checklist-scheduling.update', $id) }}" id="checklistScheduler"> @method('PUT')
        @csrf
        <div class="row mt-4">

                <div class="card mb-2">
                    <div class="card-body">
                        <h4> Scheduling </h4>

                        <div class="mb-3">
                            <label for="checklist" class="form-label"> Template </label>
                            <input type="text" class="form-control" value="{{ $checklistScheduling->checklist->name }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="loc" class="form-label"> Locations <i id="loctip" class="bi bi-info-circle-fill" data-bs-toggle="tooltip" data-bs-placement="right" title="When you do not select any location. all locations of selected maker employee will be considered."></i> </label>
                            @forelse ($checklistScheduling->children()->groupBy('store_id')->get() as $item)
                                <input type="text" class="form-control" value="{{ $item->actstore->code }} - {{ $item->actstore->name }}" disabled>
                            @empty
                            @endforelse
                        </div>
                      
                        <div class="mb-3">
                            <label for="type">Frequency Type </label>
                            @if($checklistScheduling->frequency_type == 0) <input type="text" class="form-control" value="Every Hour" disabled/> @endif 
                            @if($checklistScheduling->frequency_type == 1) <input type="text" class="form-control" value="Every N Hours" disabled/> @endif
                            @if($checklistScheduling->frequency_type == 2) <input type="text" class="form-control" value="Daily" disabled/> @endif
                            @if($checklistScheduling->frequency_type == 3) <input type="text" class="form-control" value="Every N Days" disabled/> @endif
                            @if($checklistScheduling->frequency_type == 4) <input type="text" class="form-control" value="Weekly" disabled/> @endif
                            @if($checklistScheduling->frequency_type == 5) <input type="text" class="form-control" value="Biweekly" disabled/> @endif
                            @if($checklistScheduling->frequency_type == 6) <input type="text" class="form-control" value="Monthly" disabled/> @endif 
                            @if($checklistScheduling->frequency_type == 7) <input type="text" class="form-control" value="Bimonthly" disabled/> @endif 
                            @if($checklistScheduling->frequency_type == 8) <input type="text" class="form-control" value="Quarterly" disabled/> @endif 
                            @if($checklistScheduling->frequency_type == 9) <input type="text" class="form-control" value="Semi Annually" disabled/> @endif 
                            @if($checklistScheduling->frequency_type == 10) <input type="text" class="form-control" value="Annually" disabled/> @endif
                            @if($checklistScheduling->frequency_type == 11) <input type="text" class="form-control" value="Speicific Week Days" disabled/> @endif 
                            @if($checklistScheduling->frequency_type == 12) <input type="text" class="form-control" value="Once" disabled/> @endif 
                        </div>

                        <div class="mb-3">
                            <div id="specific_days_field" @if(!in_array($checklistScheduling->frequency_type, [11])) style="display: none;" @endif>
                                <label for="specific_days">Specific Days:</label>
                                <select name="specific_days[]" id="specific_days" class="form-control" multiple disabled>
                                    <option @if(in_array('monday', explode(',', $checklistScheduling->weekdays))) selected @endif value="monday">Every Monday</option>
                                    <option @if(in_array('tuesday', explode(',', $checklistScheduling->weekdays))) selected @endif value="tuesday">Every Tuesday</option>
                                    <option @if(in_array('wednesday', explode(',', $checklistScheduling->weekdays))) selected @endif value="wednesday">Every Wednesday</option>
                                    <option @if(in_array('thursday', explode(',', $checklistScheduling->weekdays))) selected @endif value="thursday">Every Thursday</option>
                                    <option @if(in_array('friday', explode(',', $checklistScheduling->weekdays))) selected @endif value="friday">Every Friday</option>
                                    <option @if(in_array('saturday', explode(',', $checklistScheduling->weekdays))) selected @endif value="saturday">Every Saturday</option>
                                    <option @if(in_array('sunday', explode(',', $checklistScheduling->weekdays))) selected @endif value="sunday">Every Sunday</option>
                                </select>
                        
                                <label for="specific_time">Time:</label>
                                <input type="text" name="specific_time" id="specific_time" class="form-control" value="{{ $checklistScheduling->weekday_time }}" disabled>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="start_date">Start Date:</label>
                                <input type="text" name="start_date" id="start_date" class="form-control"  value="{{ date('d-m-Y H:i', strtotime($checklistScheduling->start)) }}" disabled>
                            </div>
                            @if(!($checklistScheduling->perpetual || $checklistScheduling->frequency_type == 12))
                                <div class="col-6" id="end_date_container">
                                    <label for="end_date">End Date:</label>
                                    <input type="text" name="end_date" id="end_date" class="form-control" value="{{ date('d-m-Y H:i', strtotime($checklistScheduling->end)) }}" disabled>
                                </div>
                            @endif
                        </div>

                        <div class="row mb-3" id="due_container">
                            <div class="col-6">
                                <label for="start_at">Start At <span class="text-danger"> * </span> </label>
                                <input type="text" name="start_at" id="start_at" class="form-control" value="{{ date('H:i', strtotime($checklistScheduling->start_at)) }}">
                            </div>
                            <div class="col-6">
                                <label for="completed_by">Completed By <span class="text-danger"> * </span> </label>
                                <input type="text" name="completed_by" id="completed_by" class="form-control" value="{{ date('H:i', strtotime($checklistScheduling->completed_by)) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4">
                                <label for="start_at">Grace Start Time <span class="text-danger"> * </span> </label>
                                <input type="text" name="grace_start" id="grace_start" class="form-control" value="{{ date('H:i', strtotime($checklistScheduling->start_grace_time)) }}">
                            </div>
                            <div class="col-4">
                                <label for="completed_by">Grace End Time <span class="text-danger"> * </span> </label>
                                <input type="text" name="grace_end" id="grace_end" class="form-control" value="{{ date('H:i', strtotime($checklistScheduling->end_grace_time)) }}">
                            </div>
                            <div class="col-4">
                                <label for="completed_by">Time Required <span class="text-danger"> * </span> </label>
                                <input type="text" name="time_required" id="time_required" class="form-control" value="{{ date('H:i', strtotime($checklistScheduling->hours_required)) }}">
                            </div>
                        </div>

                        @if($checklistScheduling->frequency_type != 12)
                        <div class="mb-3">
                            <label for="perpetual">
                                <input type="checkbox" id="perpetual" name="perpetual" value="1" @if($checklistScheduling->perpetual) checked @endif disabled>
                                Perpetual
                            </label>
                        </div>  
                        @endif

                        @if($checklistScheduling->frequency_type != 12)
                        <div class="mb-3">
                            <input type="checkbox" id="do_not_allow_late_submission" name="do_not_allow_late_submission" value="1" @if($checklistScheduling->do_not_allow_late_submission) checked @endif readonly/>
                            <label for="do_not_allow_late_submission"> Do not allow late submission </label>
                        </div>
                        @endif

                    </div>
                </div>

                <div class="card mb-2">
                    <div class="card-body">
                        <h4> Maker </h4>
                        
                        <div class="mb-3">
                            <label for="maker_employee" class="form-label"> Employee </label>
                            @forelse ($checklistScheduling->children()->groupBy('user_id')->get() as $item)
                            <input type="text" class="form-control" value="{{ $item->user->employee_id }} - {{ $item->user->name }} {{ $item->user->middle_name }} {{ $item->user->last_name }}" disabled>
                            @empty                                    
                            @endforelse
                        </div>   

                    </div>
                </div>

                <div class="card mb-2">
                    <div class="card-body">
                        <h4> Checker </h4>

                        <div class="mb-3">
                            <label for="checker_employee" class="form-label"> Employee <span class="text-danger"> * </span> </label>
                            @if(isset($checklistScheduling->checker))
                                <input type="text" class="form-control" value="{{ $checklistScheduling->checker->employee_id }} - {{ $checklistScheduling->checker->name }} {{ $checklistScheduling->checker->middle_name }} {{ $checklistScheduling->checker->last_name }}" disabled>
                            @endif
                        </div>   

                    </div>
                </div>

        </div>
        <button type="submit" class="btn btn-primary actualSubmitButton">Save</button>
        <a href="{{ route('checklist-scheduling.index') }}" class="btn btn-default">Back</a>
        </form>
    </div>

    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/jquery.datetimepicker.js') }}"></script>
    <script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/select2.min.js') }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {

            $('#loctip').tooltip()

            $('#maker_role').select2({
                placeholder: 'Select Role',
                allowClear: true,
                width: '100%',
                theme: 'classic'
            }).on('change', function () {
                $('#maker_employee').val(null).trigger('change');
            });

            $('#checker_role').select2({
                placeholder: 'Select Role',
                allowClear: true,
                width: '100%',
                theme: 'classic'
            }).on('change', function () {
                $('#checker_employee').val(null).trigger('change');
            });

            $('#checker_employee').select2({
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
                                return $('#checker_role option:selected').val();
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

            $('#loc').select2({
                placeholder: 'Select location',
                allowClear: true,
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('stores-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}",
                            showCode: 1
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
            }).on('change', function () {

            });

            $('#start_at').datetimepicker({
                format: 'H:i',
                datepicker: false
            });

            $('#completed_by').datetimepicker({
                format: 'H:i',
                datepicker: false
            });

            $('#grace_start').datetimepicker({
                format: 'H:i',
                datepicker: false
            });

            $('#grace_end').datetimepicker({
                format: 'H:i',
                datepicker: false
            });

            $('#time_required').datetimepicker({
                format: 'H:i',
                datepicker: false
            });

            function parseTime(str) {
                const [hours, minutes] = str.split(':').map(Number);
                return hours * 60 + minutes;
            }

            $('#checklistScheduler').validate({
                rules: {
                    checklist: {
                        required: true
                    },
                    type: {
                        required: true
                    },
                    interval: {
                        required: function(element) {
                            return $('#type').val() === 'hourly' || $('#type').val() === 'daily';
                        }
                    },
                    'specific_days[]': {
                        required: function(element) {
                            return $('#type').val() === 'specific_days';
                        }
                    },
                    start_date: {
                        required: true
                    },
                    end_date: {
                        required: function(element) {
                            return !$('#perpetual').is(':checked');
                        }
                    }
                },
                messages: {
                    checklist: {
                        required: "Please select a checklist"
                    },
                    type: {
                        required: "Please select a frequency type"
                    },
                    interval: {
                        required: "Please enter an interval"
                    },
                    'specific_days[]': {
                        required: "Please select specific days"
                    },
                    start_date: {
                        required: "Please select a start date"
                    },
                    end_date: {
                        required: "Please select an end date"
                    }
                },
                errorPlacement: function(error, element) {
                    error.appendTo(element.parent("div"));
                }
            });

            $('#perpetual').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#end_date').val('');
                }
            });

            $('#type').select2({
                placeholder: 'Select Frequency',
                allowClear: true,
                width: '100%',
                theme: 'classic'
            }).on('change', function() {
                if (!($('#type option:selected').val() == 'hourly' || $('#type option:selected').val() ==
                        'daily')) {
                    $('#interval').val('');
                } else if ($('#type option:selected').val() != 'specific_days') {
                    $('#specific_days').val(null).trigger('change');
                    $('#specific_time').val('');
                }
            });

            $('#checklist').select2({
                placeholder: 'Select Checklist Template',
                allowClear: true,
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('checklists-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,
                            _token: "{{ csrf_token() }}",
                            type: 1
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

            $('#specific_days').select2({
                placeholder: 'Select Frequency',
                allowClear: true,
                width: '100%',
                theme: 'classic'
            });

            $('#start_date').datetimepicker({
                format: 'd-m-Y H:i'
            });

            $('#end_date').datetimepicker({
                format: 'd-m-Y H:i'
            });

            $('#specific_time').datetimepicker({
                datepicker: false,
                format: 'H:i'
            });

        });

        function toggleEndDate(isChecked) {
            const endDateField = document.getElementById('end_date_container');
            endDateField.style.display = isChecked ? 'none' : 'block';
            if (isChecked) {
                document.getElementById('end_date').value = '';
            }
        }

        function toggleIntervalAndDays(type) {
            document.getElementById('interval_field').style.display = type === 'hourly' || type === 'daily' ? 'block' :
                'none';
            document.getElementById('specific_days_field').style.display = type === 'specific_days' ? 'block' : 'none';

            const endDateField = document.getElementById('end_date_container');
            endDateField.style.display = type == 'once' ? 'none' : 'block';

            const thisEle = document.getElementById('perpetual-container');
            thisEle.style.display = type == 'once' ? 'none' : 'block';
        }
    </script>
@endpush
