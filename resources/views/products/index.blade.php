@extends('layouts.app',['title' => $title, 'subTitle' => $subTitle,'datatable' => true, 'select2' => true])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                @if(auth()->user()->can('products.create'))
                <a href="{{ route('products.create') }}" class="btn btn-primary float-end">
                    <i class="fa fa-plus"></i> Add New Product
                </a>
                @endif
                <div class="row">
                    <div class="col-md-3">
                        <select id="filter-status" class="form-select select2">
                            <option value="">All Status</option>
                            <option value="1">Enable</option>
                            <option value="0">Disable</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="filter-category" class="form-select select2">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="datatables-reponsive" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>SKU</th>
                            <th>Amount</th>
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
    $('#filter-status').select2({
        allowClear: true,
        placeholder: 'Select status',
        width: '100%',
        theme: 'classic'
    });
    $('#filter-category').select2({
        allowClear: true,
        placeholder: 'Select category',
        width: '100%',
        theme: 'classic',
        ajax: {
            url: "{{ route('product-category-list') }}",
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
    let dataTable = $('#datatables-reponsive').DataTable({
        pageLength : 10,
        searching: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route(Request::route()->getName()) }}",
            type: "GET",
            data: {
                filter_status: function() {
                    return $("#filter-status").val();
                },
                filter_category: function() {
                    return $("#filter-category").val();
                }
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'category_name', orderable: false, searchable: false },
            { data: 'sku' },
            { data: 'amount' },
            { data: 'status' },
            { data: 'action', orderable: false, searchable: false }
        ],
    });
    $('#filter-status, #filter-category').on('change', function() {
        dataTable.ajax.reload();
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
                    data: { _token: '{{ csrf_token() }}' },
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
});
</script>
@endpush 