@extends('layouts.app-master')

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/css/jquery.datetimepicker.css') }}">
    <link href="{{ asset('assets/css/skip-style.css') }}" rel="stylesheet" />

    <style>
        
        label.error {
            color: #af0e0e;            
        }

        .xdsoft_datetimepicker {
            height: 400px!important;
            width: 700px!important;
        }

        .xdsoft_datepicker {
            width: 88%!important;
            height: 100%!important;
        }

        .xdsoft_datetimepicker .xdsoft_calendar {
            height: 92%!important;
        }

        .xdsoft_timepicker {
            height: 92%!important;
        }

        .xdsoft_mounthpicker {
            width: 100%!important;
            height: 30px!important;
            display: flex!important;
            flex-direction: row!important;
            align-items: center!important;
            justify-content: space-between!important;
        }

        .xdsoft_datetimepicker .xdsoft_calendar table {
            height: 100%!important;
        }

        .xdsoft_time_box {
            height: 95%!important;
        }
    </style>
@endpush

@section('content')
    <div class="bg-light p-4 rounded">

        <div class="container mt-4">

            <form method="POST" class="skip-edit-form" action="{{ route('tasks.update', $id) }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label for="code" class="form-label">Task # <span class="text-danger"> * </span> </label>
                    <input value="{{ old('code', $task->code) }}" 
                        type="text" 
                        class="form-control text-uppercase" 
                        name="code" 
                        placeholder="Task ID" required>

                    @if ($errors->has('code'))
                        <span class="text-danger text-left">{{ $errors->first('code') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="jobtype" class="form-label"> Job Type <span class="text-danger"> * </span> </label>
                    <input type="text" value="{{ $task->job_type == 0 ? ( $task->service_type == '1' ? 'Service' : 'Rental') : 'Removal' }}" class="form-control" readonly>

                    @if ($errors->has('jobtype'))
                        <span class="text-danger text-left">{{ $errors->first('jobtype') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="jobtype" class="form-label"> Service Type </label>
                    <input type="text" value="{{ $task->service_type == 1 ? ( $task->service_type == '1' ? 'Service' : 'Rental') : 'Rental' }}" class="form-control" readonly>
                    @if ($errors->has('servicetype'))
                        <span class="text-danger text-left">{{ $errors->first('servicetype') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="customer" class="form-label">Customer <span class="text-danger"> * </span> </label>
                    <select name="customer" id="customer" required>
                        @if(isset($task->customer->id))
                            <option value="{{ $task->customer->id }}" selected> {{ $task->customer->name }} - {{ $task->customer->code }} </option>
                        @endif
                    </select>

                    @if ($errors->has('customer'))
                        <span class="text-danger text-left">{{ $errors->first('customer') }}</span>
                    @endif
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger"> * </span> </label>
                    <input type="email" name="email" id="email" placeholder="Email" class="form-control" value="{{ old('email', $task->email) }}" required>

                    @if ($errors->has('email'))
                        <span class="text-danger text-left">{{ $errors->first('email') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Location  </label>
                    <select name="location" id="location">
                        @if(isset($task->location->id))
                            <option value="{{ $task->location->id }}" data-address="{{ $task->location->address }}" data-lat="{{ $task->location->latitude }}" data-long="{{ $task->location->longitude }}" > {{ $task->location->address }} - {{ $task->location->code }} </option>
                        @endif                                
                    </select>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address <span class="text-danger"> * </span> </label>
                    <textarea id="fetch-location" class="form-control" placeholder="Address" name="address" required>{{ old('address', $task->address) }}</textarea>

                    @if ($errors->has('address'))
                        <span class="text-danger text-left">{{ $errors->first('address') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="lat" class="form-label">Latitude  </label>
                    <input type="text" name="lat" id="lat" placeholder="Latitude" class="form-control" value="{{ old('lat', $task->location->latitude ?? '') }}" disabled>

                    @if ($errors->has('lat'))
                        <span class="text-danger text-left">{{ $errors->first('lat') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="long" class="form-label">Longitude </label>
                    <input type="text" name="long" id="long" placeholder="Longitude" class="form-control" value="{{ old('long', $task->location->longitude ?? '') }}" disabled>

                    @if ($errors->has('long'))
                        <span class="text-danger text-left">{{ $errors->first('long') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="date" class="form-label">Task Date <span class="text-danger"> * </span> </label>
                    <input type="text" name="date" placeholder="Task Date" id="date" class="form-control" value="{{ old('date', date('d-m-Y H:i', strtotime($task->task_date))) }}" required>

                    @if ($errors->has('date'))
                        <span class="text-danger text-left">{{ $errors->first('date') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="skip" class="form-label"> Current Unit <span class="text-danger"> * </span> </label>
                    <select name="skip" id="skip" required>
                        @if(isset($task->assignedskip->id))
                            <option value="{{ $task->assignedskip->id }}" selected> {{ $task->assignedskip->code }} - {{ $task->assignedskip->series }} </option>
                        @endif
                    </select>

                    @if ($errors->has('skip'))
                        <span class="text-danger text-left">{{ $errors->first('skip') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="taskskip" class="form-label"> Replaced Skip </label>
                    <select name="taskskip" id="taskskip">
                        @if(isset($task->skipusedintask->id))
                            <option value="{{ $task->skipusedintask->id }}" selected> {{ $task->skipusedintask->code }} - {{ $task->skipusedintask->series }} </option>
                        @endif                                
                    </select>

                    @if ($errors->has('taskskip'))
                        <span class="text-danger text-left">{{ $errors->first('taskskip') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="short_desc" class="form-label">Short Description </label>
                    <textarea name="shortdesc" id="shortdesc" class="form-control" placeholder="Short Description">{{ old('shortdesc', $task->short_description) }}</textarea>

                    @if ($errors->has('shortdesc'))
                        <span class="text-danger text-left">{{ $errors->first('shortdesc') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="desc" class="form-label"> Description </label>
                    <textarea name="desc" id="desc" class="form-control" placeholder="Description">{{ old('desc', $task->description) }}</textarea>

                    @if ($errors->has('desc'))
                        <span class="text-danger text-left">{{ $errors->first('desc') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="driver" class="form-label">Driver <span class="text-danger"> * </span> </label>
                    <select name="driver" id="driver" required>
                        @if(isset($task->driver->id))
                            <option value="{{ $task->driver->id }}" selected> {{ $task->driver->name }} - {{ $task->driver->code }} </option>
                        @endif
                    </select>

                    @if ($errors->has('driver'))
                        <span class="text-danger text-left">{{ $errors->first('driver') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="secondman" class="form-label">Second Man</label>
                    <select name="secondman" id="secondman">
                        @if(isset($task->second->id))
                            <option value="{{ $task->second->id }}" selected> {{ $task->second->name }} - {{ $task->second->code }} </option>
                        @endif
                    </select>

                    @if ($errors->has('secondman'))
                        <span class="text-danger text-left">{{ $errors->first('secondman') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="vehicle" class="form-label">Vehicle</label>
                    <select name="vehicle" id="vehicle">
                        @if(isset($task->vhcl->id))
                            <option value="{{ $task->vhcl->id }}" selected> {{ $task->vhcl->vtype->description ?? '' }} - {{ $task->vhcl->code }} </option>
                        @endif
                    </select>

                    @if ($errors->has('vehicle'))
                        <span class="text-danger text-left">{{ $errors->first('vehicle') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="jobdate" class="form-label">Job Start Date </label>
                    <input type="text" name="jobdate" id="jobdate" placeholder="Job Start Date" class="form-control" value="{{ old('jobdate', !empty($task->job_started_at) ? date('d-m-Y H:i', strtotime($task->job_started_at)) : '') }}">

                    @if ($errors->has('jobdate'))
                        <span class="text-danger text-left">{{ $errors->first('jobdate') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="job_completed_at" class="form-label">Job Completed Date </label>
                    <input type="text" name="job_completed_at" id="job_completed_at" placeholder="Job Completed Date" class="form-control" value="{{ old('job_completed_at', !empty($task->job_completed_at) ? date('d-m-Y H:i', strtotime($task->job_completed_at)) : '' ) }}">

                    @if ($errors->has('job_completed_at'))
                        <span class="text-danger text-left">{{ $errors->first('job_completed_at') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger"> * </span> </label>
                    <select name="status" id="status" required>

                        @if($task->status == 0)
                            <option value="0" @if($task->status == 0) selected @endif >Ordered</option>
                        @else
                            <option value="1" @if($task->status == 1) selected @endif >Pending</option>
                            <option value="2" @if($task->status == 2) selected @endif >Cancelled</option>
                            <option value="3" @if($task->status == 3) selected @endif >On Hold</option>
                            <option value="4" @if($task->status == 4) selected @endif >In Progress</option>
                            <option value="5" @if($task->status == 5) selected @endif >Completed</option>
                        @endif
                    </select>

                    @if ($errors->has('status'))
                        <span class="text-danger text-left">{{ $errors->first('status') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="dispsite" class="form-label">Disposal Site </label>
                    <select name="dispsite" id="dispsite">
                        @if(isset($task->disposalsite->id))
                            <option value="{{ $task->disposalsite->id }}" selected> {{ $task->disposalsite->code }} - {{ $task->disposalsite->description }} </option>
                        @endif
                    </select>

                    @if ($errors->has('dispsite'))
                        <span class="text-danger text-left">{{ $errors->first('dispsite') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="dispdate" class="form-label">Disposal Date </label>
                    <input type="text" placeholder="Disposal Date" name="dispdate" id="dispdate" class="form-control" value="{{ old('dispdate', !empty($task->disposal_at) ? date('d-m-Y H:i', strtotime($task->disposal_at)) : '') }}">

                    @if ($errors->has('dispdate'))
                        <span class="text-danger text-left">{{ $errors->first('dispdate') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="load" class="form-label">Load </label>
                    <textarea name="load" class="form-control">{{ $task->load }}</textarea>
                    @if ($errors->has('load'))
                        <span class="text-danger text-left">{{ $errors->first('load') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="ackby" class="form-label">Acknowledge By </label>
                    <input type="text" placeholder="Acknowledge By" name="ackby" id="ackby" class="form-control" value="{{ old('ackby', $task->acknowledge_by) }}">

                    @if ($errors->has('ackby'))
                        <span class="text-danger text-left">{{ $errors->first('ackby') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="ackdate" class="form-label">Acknowledge Date </label>
                    <input type="text" placeholder="Acknowledge Date" name="ackdate" id="ackdate" class="form-control" value="{{ old('ackdate', !empty($task->acknowledged_at) ? date('d-m-Y H:i', strtotime($task->acknowledged_at)) : '') }}">

                    @if ($errors->has('ackdate'))
                        <span class="text-danger text-left">{{ $errors->first('ackdate') }}</span>
                    @endif
                </div>

                @if(($task->status == 0 || $task->status == 1 || $task->status == 3 || $task->status == 4) && isset($task->removal->rental->id) && $task->removal->rental->perpatual == 1)
                    @php

                    $isFinal = false;
                    $tempRental = \App\Models\Removal::find($task->removal_id);
                    if ($tempRental) {
                        $tempRental = \App\Models\Rental::find($tempRental->rental_id);
                        if ($tempRental) {
                            $finalRmvl = \App\Models\Removal::where('rental_id', $tempRental->id)->orderBy('removal_date', 'DESC')->first();
                            if ($finalRmvl->tsk->id == $task->id) {
                                $isFinal = true;
                            }
                        }
                    }

                    @endphp

                    @if(in_array($task->job_type, [1, 2]) && isset($task->removal->rental->id) && $task->removal->rental->perpatual == 1)
                    <div class="mb-3">
                        <input type="checkbox" name="the_perpetual" id="the_perpetual" @if($task->removal->rental->perpetual_2 == 1 && $task->perpetual_terminated) checked @endif style="height: 20px;width:20px;" value="1">
                        <label for="the_perpetual" class="form-label" style="position: relative;top: -3px;left: 10px;"> 
                            @if($task->removal->rental->contract_type == 0)
                                Consider this as final removal
                            @else
                                Consider this as final service
                            @endif
                        </label>
                    </div>
                    @endif

                @endif

                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('tasks.index') }}" class="btn btn-default">Back</a>
            </form>
        </div>

    </div>
@endsection

@push('js')
<script src="{{ asset('assets/js/jquery.datetimepicker.js') }}"></script>
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/js/frequency.js') }}"></script>

    <script type="text/javascript">
        $(document).ready(function() {

            $('#status').select2({
                allowClear: true,
                placeholder: 'Select a status',
                width: '100%',
                theme: 'classic',
            });

            $('#servicetype').select2({
                allowClear: true,
                placeholder: 'Select a Job Type',
                width: '100%',
                theme: 'classic',
            });

            $('#jobtype').select2({
                allowClear: true,
                placeholder: 'Select a Job Type',
                width: '100%',
                theme: 'classic',
            });

            $('#vehicle').select2({
                allowClear: true,
                placeholder: 'Select a vehicle',
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('get-vehicles-json') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery : params.term,
                            page : params.page || 1,
                            _token : "{{ csrf_token() }}"
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
                },
            }).on('change', function (e) {

            });

            $('#customer').select2({
                allowClear: true,
                placeholder: 'Select a user',
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('get-users-json') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery : params.term,
                            page : params.page || 1,
                            _token : "{{ csrf_token() }}",
                            role : "{{ Helper::$roles['customer'] }}",
                            additionalData : true,
                            active : 1,
                            hold : 0
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
                },
            }).on('change', function (e) {

            });

            $('#skip').select2({
                allowClear: true,
                placeholder: 'Select a skip',
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('get-skip-unit-json') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}",
                            type: '1',
                            @if(isset($task->assignedskip->id))
                            id: "{{ $task->assignedskip->inventory_id }}",
                            unit_id: "{{ $task->assignedskip->id }}"
                            @endif
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

            $('#taskskip').select2({
                allowClear: true,
                placeholder: 'Select a skip',
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('get-skip-unit-json') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}",
                            type: '0,1,2'
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

            $('#dispsite').select2({
                allowClear: true,
                placeholder: 'Select a disposal site',
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('get-disposal-sites-json') }}",
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

            $('#driver').select2({
                allowClear: true,
                placeholder: 'Select a driver',
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('get-users-json') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}",
                            role: "{{ Helper::$roles['driver'] }}"
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

            $('#secondman').select2({
                allowClear: true,
                placeholder: 'Select a second man',
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('get-users-json') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}",
                            role: "{{ Helper::$roles['second-man'] . ',' . Helper::$roles['driver'] }}"
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

            $('#location').select2({
                allowClear: true,
                placeholder: 'Select a location',
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('get-location-json') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,  
                            _token: "{{ csrf_token() }}",
                            user: function () {
                                return $('#customer option:selected').val();
                            }
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;

                        return {
                            results: $.map(data.items, function(item) {
                                return {
                                    id: item.id,
                                    text: item.text,
                                    address: item.address,
                                    lat: item.lat,
                                    long: item.long
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
                    $result.attr('data-address', data.address);
                    $result.attr('data-lat', data.lat);
                    $result.attr('data-long', data.long);
                    $result.text(data.text);
                    return $result;
                },
                templateSelection: function (container) {
                    $(container.element).attr("data-address", container.address);
                    $(container.element).attr("data-lat", container.lat);
                    $(container.element).attr("data-long", container.long);

                    return container.text;
                }
            }).on('change', function (e) {
                $('#fetch-location').val($('option:selected', this).attr('data-address'));
                $('#lat').val($('option:selected', this).attr('data-lat'));
                $('#long').val($('option:selected', this).attr('data-long'));
            });

            $('#date').datetimepicker({
                format:'d-m-Y H:i'
            });

            $('#jobdate').datetimepicker({
                format:'d-m-Y H:i'
            });

            $('#job_completed_at').datetimepicker({
                format:'d-m-Y H:i'
            });

            $('#dispdate').datetimepicker({
                format:'d-m-Y H:i'
            });

            $('#ackdate').datetimepicker({
                format:'d-m-Y H:i'
            });
        });
    </script>
@endpush
