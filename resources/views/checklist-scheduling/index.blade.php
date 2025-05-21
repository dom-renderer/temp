@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css"/>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--classic .select2-selection--single .select2-selection__arrow {
        height: 38px !important;
    }

    .select2-container--classic .select2-selection--single {
        height: 40px !important;
    }

    .select2-container--classic .select2-selection--single .select2-selection__clear {
        height: 37px !important;
    }

    .select2-container--classic .select2-selection--single .select2-selection__rendered {
        line-height: 39px !important;
    }

    label.error {
        color: red;
    }
</style>
@endpush

@section('content')

    <div class="bg-light p-4 rounded">
        <h1>{{ $page_title }} </h1>
        <div class="lead">
            {{ $page_description }}
            @if (auth()->user()->can('checklist-scheduling.create'))
                <a href="{{ route('checklist-scheduling.create') }}" class="btn btn-primary btn-sm float-end">Schedule</a>
            @endif
        </div>
        
        <div class="mt-2">
            @include('layouts.partials.messages')
        </div>

        <div class="row">

            <div class="col-2">
                <label class="col-form-label" for="checklist-filter"> Checklist </label>
                <select id="checklist-filter" multiple>
                    @if(session()->has('scheduled_checklist'))
                        @foreach (session()->get('scheduled_checklist') as $thisChecklistId => $thisChecklistName)
                            <option value="{{ $thisChecklistId }}" selected> {{ $thisChecklistName }} </option>
                        @endforeach
                    @endif
                </select>
            </div>

            @php
                $hasFSession = session()->has('scheduled_frequency');
                $FSession = session()->get('scheduled_frequency');
            @endphp

            <div class="col-2">
                <label class="col-form-label" for="frequency-filter"> Frequency </label>
                <select id="frequency-filter" multiple>
                    <option @if($hasFSession && in_array(12, $FSession)) selected @endif value="12"> Once </option>
                    <option @if($hasFSession && in_array(0, $FSession)) selected @endif value="0"> Every Hour </option>
                    <option @if($hasFSession && in_array(1, $FSession)) selected @endif value="1"> Every N Hours </option>
                    <option @if($hasFSession && in_array(2, $FSession)) selected @endif value="2"> Daily </option>
                    <option @if($hasFSession && in_array(3, $FSession)) selected @endif value="3"> Every N Days </option>
                    <option @if($hasFSession && in_array(4, $FSession)) selected @endif value="4"> Weekly </option>
                    <option @if($hasFSession && in_array(5, $FSession)) selected @endif value="5"> Biweekly </option>
                    <option @if($hasFSession && in_array(6, $FSession)) selected @endif value="6"> Monthly </option>
                    <option @if($hasFSession && in_array(7, $FSession)) selected @endif value="7"> Bimonthly </option>
                    <option @if($hasFSession && in_array(8, $FSession)) selected @endif value="8"> Quarterly </option>
                    <option @if($hasFSession && in_array(9, $FSession)) selected @endif value="9"> Semi Annually </option>
                    <option @if($hasFSession && in_array(10, $FSession)) selected @endif value="10"> Annually </option>
                    <option @if($hasFSession && in_array(11, $FSession)) selected @endif value="11"> Speicific Week Days </option>
                </select>
            </div>

            <div class="col-2">
                <button id="filter-data" class="btn btn-secondary" style="position: relative;top:34px;"> Search </button>
                <button id="filter-data-clear" class="btn btn-danger @if(!(session()->has('scheduled_user') || session()->has('scheduled_checklist') || session()->has('scheduled_frequency'))) d-none @endif" style="position: relative;top:34px;"> Clear </button>
            </div>

            @if(request()->has('template'))
            <div class="col-6">
                <a href="{{ route('checklist-scheduling.index') }}" class="btn btn-success float-end" style="position: relative;top:34px;"> List all </a>
            </div>
            @endif
        </div>
        
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="users-tab-pane" role="tabpanel" aria-labelledby="users-tab" tabindex="0">
                <table class="table table-striped" id="role-table" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        {{-- <th>User</th> --}}
                        <th>Checklist</th>
                        <th>Frequency</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
        

    </div> 
@endsection


@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script>
    
    $(document).ready(function($){
        $(document).on('click', '.deleteGroup', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to delete this Checklist scheduling?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $(this).parents('form').submit();
                    return true;
                } else {
                    return false;
                }
            })
        });


        let usersTable = new DataTable('#role-table', {
            "dom": '<"d-flex justify-content-between mb-2"<"user-role-table-filter-container">f>rt<"d-flex flex-column float-start mt-3"pi><"clear">',
            ajax: {
                url: "{{ route('checklist-scheduling.index') }}",
                data: function ( d ) {
                    return $.extend( {}, d, {
                        id: "{{ $id }}",
                        user: $('#user-filter').val(),
                        checklist: $('#checklist-filter').val(),
                        frequency: $('#frequency-filter').val()
                    });
                }
            },
            processing: false,
            ordering: false,
            serverSide: true,
            columns: [
                //  { data: 'user_name' },
                 { data: 'checklist_name' },
                 { data: 'freq' },
                 { data: 'action' }
            ],
            initComplete: function(settings) {

            }
        });


        $('#user-filter').select2({
            placeholder: "Select a User",
            allowClear: true,
            width: "100%",
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
                        roles: "{{ implode(',', [Helper::$roles['store-manager'],Helper::$roles['store-employee'], Helper::$roles['store-cashier'], Helper::$roles['divisional-operations-manager'], Helper::$roles['head-of-department']  ]) }}"
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

        $('#checklist-filter').select2({
            placeholder: 'Select Checklist',
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

        $('#frequency-filter').select2({
            placeholder: 'Select Frequency',
            allowClear: true,
            width: '100%',
            theme: 'classic'
        });

        $(document).on('click', '#filter-data', function () {
            usersTable.ajax.reload();

            let userFilter = $('#user-filter').val();
            let checklistFilter = $('#checklist-filter').val();
            let frequencytFilter = $('#frequency-filter').val();

            if (userFilter !== '' || checklistFilter !== '' || frequencytFilter != '') {
                $('#filter-data-clear').removeClass('d-none');
            } else {
                $('#filter-data-clear').addClass('d-none');
            }
        });

        $(document).on('click', '#filter-data-clear', function () {
            if (!$('#filter-data-clear').hasClass('d-none')) {
                $('#filter-data-clear').addClass('d-none');
            }

            $('#user-filter').val(null).trigger('change');
            $('#checklist-filter').val(null).trigger('change');
            $('#frequency-filter').val(null).trigger('change');

            usersTable.ajax.reload();
        });
        
    });
 </script>  
@endpush
