@extends('layouts.app',['title' => $title, 'subTitle' => $subTitle,'datatable' => true, 'select2' => true, 'datepicker' => true])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                {{-- Filters --}}
                @if(auth()->user()->can('jobs.create'))
                <a href="{{ route('jobs.create') }}" class="btn btn-primary float-end">
                    <i class="fa fa-plus"></i> Add New Job 
                </a>
                @endif
                {{-- Filters --}}
            </div>
            <div class="card-body">
                <table id="datatables-reponsive" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection


@push('js')
<script>
    $(document).ready(function () {
        let dataTable = $('#datatables-reponsive').DataTable({
            pageLength : 10,
            searching: false,
            processing: true,
            serverSide: true,
            ajax: {
                "url": "{{ route(Request::route()->getName()) }}",
                "type": "GET",
                "data" : {
                    filter_status:function() {
                        return $("#filter-status").val();
                    }
                }
            },
            columns: [
                {
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                },
                {
                    data: 'title',
                },
                {
                    data: 'status',
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false,
                }
            ],
        });

        $(document).on('click', '#deleteRow', function () {
            let url = $(this).data('row-route');
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.success, 'success');
                                dataTable.ajax.reload();
                            } else if (response.error) {
                                Swal.fire('Error', response.error, 'error');
                            }
                        },
                        error: function (xhr) {
                            let msg = 'An error occurred.';
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                msg = xhr.responseJSON.error;
                            }
                            Swal.fire('Error', msg, 'error');
                        }
                    });
                }
            });
        });



        $(document).on('click', '.reschedule-btn', function () {
            let url = $(this).data('url');
            Swal.fire({
                title: 'Reschedule Job',
                html:
                    '<input id="swal-datepicker" class="form-control mb-3" placeholder="Select date">' +
                    '<textarea id="swal-reason" class="form-control" placeholder="Reason"></textarea>',
                showCancelButton: true,
                confirmButtonText: 'Submit',
                didOpen: () => {
                    $('#swal-datepicker').datepicker({
                        format: 'dd-mm-yyyy', 
                        autoclose: true,
                        dateFormat: 'dd-mm-yy',
                        todayHighlight: true
                    });
                },
                preConfirm: () => {
                    const date = $('#swal-datepicker').val();
                    const reason = $('#swal-reason').val();
                    if (!date || !reason) {
                        Swal.showValidationMessage('Please select a date and enter a reason');
                        return false;
                    }
                    return { date, reason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            reschedule_date: result.value.date,
                            reason: result.value.reason
                        },
                        success: function (response) {
                            if (response.status) {
                                Swal.fire('Success', response.message, 'success');
                                $('#datatables-reponsive').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            Swal.fire('Error', 'Failed to reschedule job', 'error');
                        }
                    });
                }
            });
        });

        $(document).on('change', '.change-status', function () {
            let select = $(this);
            let url = select.data('url');
            let newStatus = select.val();
            let oldStatus = select.data('old') || select.find('option:selected').val();
            
            if (newStatus === oldStatus) return;

            Swal.fire({
                title: 'Are you sure?',
                text: `Change status from ${oldStatus} to ${newStatus}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (newStatus === 'CANCELLED' && oldStatus !== 'CANCELLED') {
                        Swal.fire({
                            title: 'Cancel Job',
                            html:
                                '<input id="cancel-amount" type="number" class="form-control mb-3" placeholder="Amount">' +
                                '<textarea id="cancel-note" class="form-control" placeholder="Description note"></textarea>',
                            showCancelButton: true,
                            confirmButtonText: 'Submit',
                            preConfirm: () => {
                                const amount = $('#cancel-amount').val();
                                const note = $('#cancel-note').val();
                                if (!amount || !note) {
                                    Swal.showValidationMessage('Amount and Note are required');
                                    return false;
                                }
                                return { amount, note };
                            }
                        }).then((cancelResult) => {
                            if (cancelResult.isConfirmed) {
                                updateStatus(url, newStatus, cancelResult.value.amount, cancelResult.value.note, select, oldStatus);
                            } else {
                                select.val(oldStatus);
                            }
                        });
                    } else {
                        updateStatus(url, newStatus, null, null, select, oldStatus);
                    }
                } else {
                    select.val(oldStatus);
                }
            });
        });

        function updateStatus(url, status, amount, note, select, oldStatus) {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status,
                    cancel_amount: amount,
                    cancel_note: note
                },
                success: function (response) {
                    if (response.status) {
                        Swal.fire('Success', response.message, 'success');
                        select.data('old', status);
                        $('#datatables-reponsive').DataTable().ajax.reload(null, false);
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to update status', 'error');
                    select.val(oldStatus);
                }
            });
        }




    });
</script>
@endpush