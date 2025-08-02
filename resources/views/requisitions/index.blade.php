@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Requisitions</h4>
                    </div>
                    <div class="col-md-6 text-end">
                        @if(auth()->user()->can('requisitions.create'))
                            <a href="{{ route('requisitions.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add New Requisition
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="filter_status">Status</label>
                        <select id="filter_status" class="form-select">
                            <option value="">All Status</option>
                            <option value="PENDING">Pending</option>
                            <option value="APPROVED">Approved</option>
                            <option value="REJECTED">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_job">Job</label>
                        <select id="filter_job" class="form-select">
                            <option value="">All Jobs</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="button" id="clear_filters" class="btn btn-secondary d-block">
                            <i class="fa fa-times"></i> Clear Filters
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="requisitions-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Job Code</th>
                                <th>Requisition Code</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Added By</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('#filter_job').select2({
        allowClear: true,
        placeholder: 'Select job',
        width: '100%',
        theme: 'classic',
        ajax: {
            url: "{{ route('job-list') }}",
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
                    results: data.items,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        }
    });

    var table = $('#requisitions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route(Request::route()->getName()) }}",
            data: function(d) {
                d.filter_status = $('#filter_status').val();
                d.filter_job = $('#filter_job').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'job_code', name: 'job_code'},
            {data: 'code', name: 'code'},
            {data: 'total', name: 'total'},
            {data: 'status', name: 'status'},
            {data: 'added_by_name', name: 'added_by_name'},
            {data: 'created_at', name: 'created_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });

    $('#filter_status, #filter_job').on('change', function() {
        table.draw();
    });

    $('#clear_filters').on('click', function() {
        $('#filter_status').val('').trigger('change');
        $('#filter_job').val('').trigger('change');
        table.draw();
    });

    $(document).on('click', '#deleteRow', function() {
        var route = $(this).data('row-route');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                response.success,
                                'success'
                            );
                            table.draw();
                        } else {
                            Swal.fire(
                                'Error!',
                                response.error,
                                'error'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Something went wrong!';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        Swal.fire(
                            'Error!',
                            errorMessage,
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
@endpush 