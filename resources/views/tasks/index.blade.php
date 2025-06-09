@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/jquery.datetimepicker.css') }}">
<link href="{{ asset('assets/css/skip-style.css') }}" rel="stylesheet" />

<style>
ul.nav-tabs {
    border: 0 !important;
    flex-wrap: nowrap;
    gap: 5px;
}
ul.nav-tabs li {
    margin: 0;
    width: 100%;
}
ul.nav-tabs li  button.nav-link {
    padding: 15px;
    width: 100%;
    justify-content: center;
}

span.select2 span.select2-container span.select2-container--classic {
    border: 1px solid #5897fb!important;
}

label.error {
    color: red;
}

</style>
@endpush

@section('content')

<div class="row" style="align-items: center;">
    <div class="col-3">
        <h1 class="panel-title">{{ $page_title }}
            <span>{{ $page_description }}</span>
        </h1>
    </div>

    <div class="col-9">
        <button class="me-2 btn btn-sm btn-primary float-end reassign-vehicle d-none"  data-bs-toggle="modal" data-bs-target="#assignVehicle"> Reassign Vehicle </button>
        <button class="me-2 btn btn-sm btn-primary float-end reassign-driver-sman d-none" data-bs-toggle="modal" data-bs-target="#assignDriverSecondman"> Reassign Driver/Second-man </button>
    </div>
</div>

    <div class="mt-2">
            @include('layouts.partials.messages')
            <div class="d-flex flex-row-reverse">
                <button data-action="5" data-title="Are you sure you want to make all checked tasks as completed?" class="me-2 action-btn btn btn-sm btn-success float-end completed-checked d-none"> Complete </button>
                <button data-action="4" data-title="Are you sure you want to make all checked tasks as in progress?" class="me-2 action-btn btn btn-sm btn-warning float-end inprogress-checked d-none"> In Progress </button>
                <button data-action="3" data-title="Are you sure you want to make all checked tasks as on hold?" class="me-2 action-btn btn btn-sm btn-secondary float-end onhold-checked d-none"> On Hold </button>
                <button data-action="2" data-title="Are you sure you want to make all checked tasks as cancelled?" class="me-2 action-btn btn btn-sm btn-danger float-end cancel-checked d-none"> Cancel </button>
                <button data-action="1" data-title="Are you sure you want to make all checked tasks as pending?" class="me-2 action-btn btn btn-sm btn-warning float-end pending-checked d-none"> Pending </button>
                <button data-action="0" data-title="Are you sure you want approve all of the checked tasks?" class="me-2 action-btn btn btn-sm btn-success float-end approve-checked d-none"> Approve </button>
            </div>
        </div>

        <ul class="nav nav-tabs mt-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link @if(!session()->has('dashboard_task_status')) active @endif" id="users-tab" data-status="all" data-bs-toggle="tab" type="button" role="tab" aria-controls="users-tab-pane" aria-selected="true"> All </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link @if(session()->has('dashboard_task_status') && session()->get('dashboard_task_status') === '0') active @endif" id="archived-users-tab" data-status="0" data-bs-toggle="tab"  type="button" role="tab" aria-controls="archived-users-tab-pane" aria-selected="false"> Ordered </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link @if(session()->has('dashboard_task_status') && session()->get('dashboard_task_status') === '1') active @endif" id="archived-users-tab" data-status="1" data-bs-toggle="tab"  type="button" role="tab" aria-controls="archived-users-tab-pane" aria-selected="false"> Pending </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archived-users-tab" data-status="2" data-bs-toggle="tab"  type="button" role="tab" aria-controls="archived-users-tab-pane" aria-selected="false"> Cancelled </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archived-users-tab" data-status="3" data-bs-toggle="tab"  type="button" role="tab" aria-controls="archived-users-tab-pane" aria-selected="false"> On Hold </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link @if(session()->has('dashboard_task_status') && session()->get('dashboard_task_status') === '4') active @endif" id="archived-users-tab" data-status="4" data-bs-toggle="tab"  type="button" role="tab" aria-controls="archived-users-tab-pane" aria-selected="false"> In Progress </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link @if(session()->has('dashboard_task_status') && session()->get('dashboard_task_status') === '5') active @endif" id="archived-users-tab" data-status="5" data-bs-toggle="tab"  type="button" role="tab" aria-controls="archived-users-tab-pane" aria-selected="false"> Completed </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archived-users-tab" data-status="invoiced" data-bs-toggle="tab"  type="button" role="tab" aria-controls="archived-users-tab-pane" aria-selected="false"> Invoiced </button>
            </li>
        </ul>

                <div class="row filter-wrap flex-box mt-5 mb-5">
                    <div class="filter-box mb-4">
                        <!-- <label for="code" class="form-label"> Job </label> <br> -->
                        <input type="text" placeholder="Job ID" class="form-control" id="code">
                    </div>

                    <div class="filter-box">
                        <!-- <label for="driver" class="form-label"> Driver </label> <br> -->
                        <select id="driver">
                            @if(session()->has('dashboard_filter_driver'))
                                <option value="{{ session()->get('dashboard_filter_driver')->id }}" selected> {{ session()->get('dashboard_filter_driver')->name }} - {{ session()->get('dashboard_filter_driver')->code }} </option>
                            @endif
                        </select>
                    </div>

                    <div class="filter-box">
                        <!-- <label for="customer" class="form-label"> Customer </label> <br> -->
                        <select id="customer"></select>
                    </div>

                    <div class="filter-box">
                        <!-- <label for="from" class="form-label"> From Date </label> <br> -->
                        <input type="text" id="from" placeholder="From Date" class="form-control" style="width: 100%;" value="{{ session()->has('dashboard_filter_from_date') ? session()->get('dashboard_filter_from_date') : date('d-m-Y') }}">
                    </div>

                    <div class="filter-box">
                        <!-- <label for="rental" class="form-label"> To Date </label> <br> -->
                        <input type="text" id="to" placeholder="To Date" class="form-control" style="width: 100%;" value="{{ session()->has('dashboard_filter_to_date') ? session()->get('dashboard_filter_to_date') : date('d-m-Y') }}">
                    </div>

                    <div class="filter-box">
                       <!--  <label for="jobtype" class="form-label"> Job Type </label> <br> -->
                        <select id="jobtype" class="form-control">
                            <option value=""> Select Job Type </option>
                            <option value="0" @if(session()->has('dashboard_job_type') && session()->get('dashboard_job_type') == '1') selected @endif> New Rental </option>
                            <option value="1"> Removal </option>
                            <option value="2" @if(session()->has('dashboard_job_type') && session()->get('dashboard_job_type') == '2') selected @endif> Final Removal </option>
                        </select>
                    </div>

                    <div class="filter-box">
                        <!-- <label for="archived" class="form-label"> Type </label> <br> -->
                        <select id="archived" class="form-control">
                            <option value=""> Type </option>
                            <option value="1"> Not Archived </option>
                            <option value="2"> Archived </option>
                        </select>
                    </div>

                    <div class="filter-box">
                        <button class="btn btn-primary" id="search"> Search </button>
                        <button class="btn btn-danger" id="clear"> Clear </button>
                        <button type="button" class="btn btn-primary btn-sort" name="operation" value="sort"> Sort Tasks </button>
                    </div>

                    <!-- <div class="filter-box" style="align-content:end!important;">
                        <button type="button" class="btn btn-primary btn-sort mt-2" name="operation" value="sort"> Sort Tasks </button>
                    </div> -->
                </div>

                <table class="table skip-table" id="role-table" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="checkbox-20" id="check-all">
                        </th>
                        <th></th>
                        <th>Job ID</th>
                        <th>Task Code</th>
                        <th>Rental Code</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th id="status-toggle-column">Status</th>
                        <th>Task Datetime</th>
                        <th>Completed Datetime</th>
                        <th>Assigned Driver</th>
                        <th>Approved</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

        



<div class="modal fade" id="assignDriverSecondman" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="assignDriverSecondmanLabel" aria-hidden="true">
    <form action="{{ route('reassign-driver-secondman') }}" method="POST" id="assignDriverSecondmanForm"> @csrf
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="assignDriverSecondmanLabel">Reassign Driver / Second-man</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-12" style="margin-bottom: 50px;">
                        <select name="assigning_driver" id="assigning_driver"></select>
                    </div>
                    <div class="col-12">
                        <select name="assigning_sman" id="assigning_sman"></select>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="assignVehicle" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="assignVehicleLabel" aria-hidden="true">
    <form action="{{ route('reassign-vehicle') }}" method="POST" id="assignVehicleForm"> @csrf
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="assignVehicleLabel">Reassign Vehicle</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-12" style="margin-bottom: 50px;">
                        <select name="assigning_vehicle" id="assigning_vehicle"></select>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
            </div>
        </div>
    </form>
</div>

@endsection

@push('js')
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.24/themes/smoothness/jquery-ui.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.datetimepicker.js') }}"></script>
<script>

    var activeTab = 'all';

    @if(session()->has('dashboard_task_status'))
        activeTab = {{ session()->get('dashboard_task_status') }};
    @endif

    $(document).ready(function($){

        $('#assignDriverSecondmanForm').validate({
            rules: {
                assigning_driver: {
                    required: true,
                },
                assigning_sman: {
                    required: true,
                }
            },
            messages: {
                assigning_driver: {
                    required: "Please select a driver",
                },
                assigning_sman: {
                    required: "Please select a second-man",
                }
            },
            errorPlacement: function(error, element) {
                error.appendTo(element.parent("div"));
            },
            submitHandler: function (form, event) {
                event.preventDefault();

                let toBeRemoved = $('.single-check').toArray().map(function(el) {
                    if ($(el).is(':checked')) {
                        return $(el).attr('data-id');
                    }
                }).join(',');

                if (toBeRemoved.length > 0) {
                    $.ajax({
                        url: "{{ route('reassign-driver-secondman') }}",
                        type: 'POST',
                        data: {
                            idString: toBeRemoved,
                            _token: "{{ csrf_token() }}",
                            driver: function () {
                                return $('#assigning_driver').val()
                            },
                            sman: function () {
                                return $('#assigning_sman').val()
                            },
                        },
                        beforeSend: function() {
                            $('#check-all').prop('checked', false);
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire('success', response.message,
                                    'success');
                                usersTable.ajax.reload();
                                $('#assignDriverSecondman .btn-close').click();
                            } else {
                                Swal.fire('error', response.message, 'error');
                            }
                        }

                    });
                } else {
                    $('#check-all').prop('checked', false);
                    if (!$('.approve-checked').hasClass('d-none')) {
                        $('.approve-checked').addClass('d-none');
                    }
                }
            }
        });

        $('#assignVehicleForm').validate({
            rules: {
                assigning_vehicle: {
                    required: true,
                }
            },
            messages: {
                assigning_vehicle: {
                    required: "Please select a vehicle",
                }
            },
            errorPlacement: function(error, element) {
                error.appendTo(element.parent("div"));
            },
            submitHandler: function (form, event) {
                event.preventDefault();

                let toBeRemoved = $('.single-check').toArray().map(function(el) {
                    if ($(el).is(':checked')) {
                        return $(el).attr('data-id');
                    }
                }).join(',');

                if (toBeRemoved.length > 0) {
                    $.ajax({
                        url: "{{ route('reassign-vehicle') }}",
                        type: 'POST',
                        data: {
                            idString: toBeRemoved,
                            _token: "{{ csrf_token() }}",
                            vehicle: function () {
                                return $('#assigning_vehicle').val()
                            }
                        },
                        beforeSend: function() {
                            $('#check-all').prop('checked', false);
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire('success', response.message,
                                    'success');
                                usersTable.ajax.reload();
                                $('#assignVehicle .btn-close').click();                                
                            } else {
                                Swal.fire('error', response.message, 'error');
                            }
                        }

                    });
                } else {
                    $('#check-all').prop('checked', false);
                    if (!$('.approve-checked').hasClass('d-none')) {
                        $('.approve-checked').addClass('d-none');
                    }
                }
            }
        });

        $(document).on('hidden.bs.modal', '#assignDriverSecondman', function (e) {
            if (e.namespace == 'bs.modal') {
                $('#assigning_driver').val(null).trigger('change');
                $('#assigning_sman').val(null).trigger('change');
            }
        });

        $(document).on('hidden.bs.modal', '#assignVehicle', function (e) {
            if (e.namespace == 'bs.modal') {
                $('#assigning_vehicle').val(null).trigger('change');
            }
        });


        $('[data-bs-toggle="popover"]').popover();

        function gatherRowData(that) {
            let allData = [];
            
            return new Promise(function(resolve, reject) {

                $(that).find("tr").each(function(index, el) {
                    var taskId = $(el).attr('data-taskid');
                    if (taskId) {
                        allData.push(taskId);
                    }
                });
                
                resolve(allData);
            });
        }

        $(".btn-sort").click(function() {
            alert("Drag and drop the task to change their order.")
            $("#role-table").sortable({
                items: '.sortableTR',
                cursor: 'move',
                axis: 'y',
                dropOnEmpty: false,
                start: function(e, ui) {
                    ui.item.addClass("selected");
                },
                stop: function(e, ui) {
                    ui.item.removeClass("selected");

                    gatherRowData(this).then(function(allData) {

                        $.ajax({
                            url: "{{ route('sort-tasks') }}",
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                task_ids: allData
                            },
                            success: function(response) {
                            }
                        });
                    }).catch(function(error) {

                    });
                }
            });
        });


        $(document).on('click', '.deleteGroup', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to delete this Task?',
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

        $(document).on('click', '.restoreGroup', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to restore this Task?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, restore it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $(this).parents('form').submit();
                    return true;
                } else {
                    return false;
                }
            })
        });

        $(document).on('click', '.importantGroup', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to make this important?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, make it important!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $(this).parents('form').submit();
                    return true;
                } else {
                    return false;
                }
            })
        });

        $(document).on('click', '.unimportantGroup', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to make this unimportant?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, make it unimportant!'
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
            ajax: {
                url: "{{ route('tasks.index') }}",
                data: function ( d ) {
                    return $.extend( {}, d, {
                        type: activeTab,
                        code: function() {
                            return $('#code').val();
                        },
                        jobtype: function() {
                            return $('#jobtype option:selected').val();
                        },
                        customer: function() {
                            return $('#customer option:selected').val();
                        },
                        driver: function() {
                            return $('#driver option:selected').val();
                        },
                        from: function() {
                            return $('#from').val();
                        },
                        to: function() {
                            return $('#to').val();
                        },
                        archived: function() {
                            return $('#archived').val();
                        }
                    });
                }
            },
            lengthMenu: [ [100, 250, 500], [100, 250, 500] ],
            searching: false,
            processing: true,
            ordering: false,
            serverSide: true,
            columns: [
                 { data: 'checkbox' },
                 { data: 'approve_single' },
                 { data: 'legacy_code' },
                 { data: 'code' },
                 { data: 'rental_code' },
                 { data: 'customer_id' },
                 { data: 'address' },
                 { data: 'status' },
                 { data: 'task_date' },
                 { data: 'job_completed_at' },
                 { data: 'driver_id' },
                 { data: 'is_approved' },
                 { data: 'action' }
            ],
            drawCallback: function(settings) {
                $('[data-bs-toggle="popover"]').popover();
                toggleActionButtons();
                $('#check-all').prop('checked', false);

                $('.approve-checked').addClass('d-none');
                $('.completed-checked').addClass('d-none');
                $('.pending-checked').addClass('d-none');
                $('.cancel-checked').addClass('d-none');
                $('.onhold-checked').addClass('d-none');
                $('.inprogress-checked').addClass('d-none');
            },
            createdRow: function(row, data, dataIndex) {
                $(row).attr('data-exclude', 'true')
                .attr('data-taskid', data.id)
                .addClass('sortableTR');

                if (activeTab == 'all') {
                    $(row).find('td').eq(7);
                    $('#status-toggle-column').removeClass('d-none');
                } else {
                    $(row).find('td').eq(7).addClass('d-none');
                    $('#status-toggle-column').addClass('d-none');
                }
            }
        });
        
        $('#from').datetimepicker({
            format: 'd-m-Y',
            timepicker: false,
            onChangeDateTime: function(dp, $input) {

            }
        });

        $('#to').datetimepicker({
            format: 'd-m-Y',
            timepicker: false,
            onChangeDateTime: function(dp, $input) {

            }
        });

        $('#search').on('click', function() {
            usersTable.ajax.reload();

            if ($('#from').val() != '' || $('#to').val() != '' || 
                $('#code').val() != '' || $('#jobtype').val() != '' ||
                $('#customer').val() != '' || $('#archived').val() != '') {
                $('#clear').removeClass('d-none');
            } else {
                if (!$('#clear').hasClass('d-none')) {
                    $('#clear').addClass('d-none');
                }
            }
        });

        $('#clear').on('click', function() {
            $('#from').val(null);
            $('#to').val(null);
            $('#code').val(null);
            $('#jobtype').val(null).trigger('change');
            $('#customer').val(null).trigger('change');
            $('#archived').val(1).trigger('change');

            if (!$(this).hasClass('d-none')) {
                $(this).addClass('d-none');
            }
            usersTable.ajax.reload();
        });

        $('#myTab .nav-link').on('click', function () {
            activeTab = $(this).attr('data-status');
            usersTable.ajax.reload();
        });

        $(document).on('change', '#check-all', function(e) {
            if ($(this).is(':checked')) {
                $('.single-check').prop('checked', true);
            } else {
                $('.single-check').prop('checked', false);
            }

            toggleActionButtons();
        });

        $(document).on('change', '.single-check', function(e) {
            if ($('.single-check').toArray().every((item) => $(item).is(':checked'))) {
                if (!$('#check-all').is(':checked')) {
                    $('#check-all').prop('checked', true);
                }
            } else {
                if ($('#check-all').is(':checked')) {
                    $('#check-all').prop('checked', false);
                }
            }

            toggleActionButtons();
        });

        function toggleActionButtons() {
            if ($('.single-check').toArray().some((item) => $(item).is(':checked')) || $('#check-all').is(':checked')) {
                if (activeTab == 'all') {
                    $('.approve-checked').removeClass('d-none');

                    $('.pending-checked').addClass('d-none');
                    $('.onhold-checked').addClass('d-none');
                    $('.inprogress-checked').addClass('d-none');
                    $('.cancel-checked').addClass('d-none');
                    $('.completed-checked').addClass('d-none');

                } else if (activeTab == '0') {
                    $('.approve-checked').removeClass('d-none');

                    $('.pending-checked').addClass('d-none');
                    $('.onhold-checked').addClass('d-none');
                    $('.inprogress-checked').addClass('d-none');
                    $('.cancel-checked').addClass('d-none');
                    $('.completed-checked').addClass('d-none');
                    
                } else if (activeTab == '1') {
                    
                    $('.pending-checked').addClass('d-none');
                    $('.approve-checked').addClass('d-none');

                    $('.cancel-checked').removeClass('d-none');
                    $('.onhold-checked').removeClass('d-none');
                    $('.inprogress-checked').removeClass('d-none');
                    $('.completed-checked').removeClass('d-none');

                } else if (activeTab == '2') {
                    $('.approve-checked').addClass('d-none');
                    $('.cancel-checked').addClass('d-none');

                    $('.pending-checked').removeClass('d-none');
                    $('.onhold-checked').removeClass('d-none');
                    $('.inprogress-checked').removeClass('d-none');
                    $('.completed-checked').removeClass('d-none');

                } else if (activeTab == '3') {
                    $('.approve-checked').addClass('d-none');
                    $('.onhold-checked').addClass('d-none');

                    $('.pending-checked').removeClass('d-none');
                    $('.cancel-checked').removeClass('d-none');
                    $('.inprogress-checked').removeClass('d-none');
                    $('.completed-checked').removeClass('d-none');
                } else if (activeTab == '4') {
                    $('.approve-checked').addClass('d-none');
                    $('.iprogress-checked').addClass('d-none');

                    $('.pending-checked').removeClass('d-none');
                    $('.cancel-checked').removeClass('d-none');
                    $('.onhold-checked').removeClass('d-none');
                    $('.completed-checked').removeClass('d-none');

                } else if (activeTab == '5') {
                    $('.approve-checked').addClass('d-none');
                    $('.completed-checked').addClass('d-none');
                    $('.pending-checked').addClass('d-none');
                    $('.cancel-checked').addClass('d-none');
                    $('.onhold-checked').addClass('d-none');
                    $('.inprogress-checked').addClass('d-none');
                } else if (activeTab == 'invoiced') {
                    $('.approve-checked').addClass('d-none');
                    $('.completed-checked').addClass('d-none');
                    $('.pending-checked').addClass('d-none');
                    $('.cancel-checked').addClass('d-none');
                    $('.onhold-checked').addClass('d-none');
                    $('.inprogress-checked').addClass('d-none');
                }

                //Assign
                $('.reassign-driver-sman').removeClass('d-none');
                $('.reassign-vehicle').removeClass('d-none');
                //Assign

            } else {
                $('.approve-checked').addClass('d-none');
                $('.completed-checked').addClass('d-none');
                $('.pending-checked').addClass('d-none');
                $('.cancel-checked').addClass('d-none');
                $('.onhold-checked').addClass('d-none');
                $('.inprogress-checked').addClass('d-none');

                //Assign
                $('.reassign-driver-sman').addClass('d-none');
                $('.reassign-vehicle').addClass('d-none');
                //Assign                
            }
        }

        $(document).on('click', '.action-btn', function(e) {
            e.preventDefault();

            let thisActionType = $(this).attr('data-action');

            Swal.fire({
                title: $(this).attr('data-title'),
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {

                    let toBeRemoved = $('.single-check').toArray().map(function(el) {
                        if ($(el).is(':checked')) {
                            return $(el).attr('data-id');
                        }
                    }).join(',');

                    if (toBeRemoved.length > 0) {
                        $.ajax({
                            url: "{{ route('tasks.approve-multiple-status') }}",
                            type: 'POST',
                            data: {
                                idString: toBeRemoved,
                                _token: "{{ csrf_token() }}",
                                action: thisActionType
                            },
                            beforeSend: function() {
                                $('#check-all').prop('checked', false);
                            },
                            success: function(response) {
                                if (response.status) {
                                    Swal.fire('success', response.message,
                                        'success');
                                    usersTable.ajax.reload();
                                } else {
                                    Swal.fire('error', response.message, 'error');
                                }
                            }

                        });
                    } else {
                        $('#check-all').prop('checked', false);
                        if (!$('.approve-checked').hasClass('d-none')) {
                            $('.approve-checked').addClass('d-none');
                        }
                    }

                    return true;
                } else {
                    return false;
                }
            })
        });


        $(document).on('click', '.approve-single-record', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to approve?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, approve it!'
            }).then((result) => {
                if (result.isConfirmed) {

                    let toBeRemoved = $(this).attr('data-rid');

                    if (toBeRemoved > 0) {
                        $.ajax({
                            url: "{{ route('tasks.approve-multiple-status') }}",
                            type: 'POST',
                            data: {
                                idString: toBeRemoved,
                                _token: "{{ csrf_token() }}",
                                action: 0
                            },
                            success: function(response) {
                                if (response.status) {
                                    Swal.fire('success', response.message,
                                        'success');
                                    usersTable.ajax.reload();
                                } else {
                                    Swal.fire('error', response.message, 'error');
                                }
                            }

                        });
                    }

                    return true;
                } else {
                    return false;
                }
            })
        });

        $(document).keyup(function (e) {
            if (e.which == 27) {
                $('.popover').remove();
            }
        });

        $('#assigning_vehicle').select2({
            allowClear: true,
            placeholder: 'Select a vehicle',
            width: '100%',
            theme: 'classic',
            dropdownParent: $('#assignVehicle'),            
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
            }
        }).on('change', function (e) {
        });

        $('#assigning_driver').select2({
            allowClear: true,
            placeholder: 'Select a driver',
            width: '100%',
            theme: 'classic',
            dropdownParent: $('#assignDriverSecondman'),            
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
                        role : "{{ implode(',', [Helper::$roles['driver'], Helper::$roles['second-man']]) }}"
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
        }).on('change', function (e) {
        });

        $('#assigning_sman').select2({
            allowClear: true,
            placeholder: 'Select a second-man',
            width: '100%',
            theme: 'classic',
            dropdownParent: $('#assignDriverSecondman'),
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
                        role : "{{ implode(',', [Helper::$roles['driver'], Helper::$roles['second-man']]) }}"
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
        }).on('change', function (e) {
        });        


        $('#customer').select2({
            allowClear: true,
            placeholder: 'Select a customer',
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
                        role : "{{ Helper::$roles['customer'] }}"
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
        }).on('change', function (e) {
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
                        searchQuery : params.term,
                        page : params.page || 1,
                        _token : "{{ csrf_token() }}",
                        role : "{{ Helper::$roles['driver'] }}"
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
        }).on('change', function (e) {
        });
    });
 </script>  
@endpush

@php
    if (session()->has('dashboard_filter_from_date')) {
        session()->forget('dashboard_filter_from_date');
    }

    if (session()->has('dashboard_filter_to_date')) {
        session()->forget('dashboard_filter_to_date');
    }

    if (session()->has('dashboard_filter_driver')) {
        session()->forget('dashboard_filter_driver');
    }

    if (session()->has('dashboard_task_status')) {
        session()->forget('dashboard_task_status');
    }

    if (session()->has('dashboard_job_type')) {
        session()->forget('dashboard_job_type');
    }
@endphp
