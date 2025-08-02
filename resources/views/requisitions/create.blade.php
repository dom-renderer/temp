@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">
<style>
    label.error {
        color: red;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('requisitions.store') }}" id="requisitionForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="job_id" class="form-label">Job <span class="text-danger">*</span></label>
                                <select name="job_id" id="job_id" class="form-select" required>
                                    <option value="">Select Job</option>
                                </select>
                                @error('job_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="multi-items">
                                <tr>
                                    <td class="row-type-container">
                                        <select class="row-type" id="row-type-0" name="requisition[0][type]" required> 
                                            <option value="INVENTORY"> Inventory </option>
                                            <option value="VENDOR"> Vendor </option>
                                        </select>
                                    </td>
                                    <td class="row-product-container">
                                        <select class="row-product" id="row-product-0" name="requisition[0][product]" required>
                                        </select>
                                    </td>
                                    <td class="row-description-container">
                                        <textarea class="row-description form-control" id="row-description-0" name="requisition[0][description]" placeholder="Description"></textarea>
                                    </td>
                                    <td class="row-quantity-container">
                                        <input type="number" min="1" class="row-quantity form-control" id="row-quantity-0" name="requisition[0][quantity]" value="1" required>
                                    </td>
                                    <td class="row-amount-container">
                                        <input type="number" min="0" step="0.01" class="row-amount form-control" id="row-amount-0" name="requisition[0][amount]" required>
                                    </td>
                                    <td class="row-total-container">
                                        <input type="number" min="0" class="row-total form-control" id="row-total-0" name="requisition[0][total]" readonly>
                                    </td>
                                    <td>
                                        <button class="btn btn-success add-row" type="button"> + </button>
                                    </td>
                                </tr>
                            </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5"> Total </td>
                                        <td colspan="2" id="grand-total-column">
                                            0.00
                                        </td>
                                    </tr>
                                </tfoot>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Create Requisition</button>
                            <a href="{{ route('requisitions.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script>
$(document).ready(function() {

    let itemRow = `
        <tr>
            <td class="row-type-container">
                <select class="row-type" id="row-type-0" name="requisition[0][type]" required>
                    <option value="INVENTORY"> Inventory </option>
                    <option value="VENDOR"> Vendor </option>
                </select>
            </td>
            <td class="row-product-container">
                <select class="row-product" id="row-product-0" name="requisition[0][product]" required>
                </select>
            </td>
            <td class="row-description-container">
                <textarea class="row-description form-control" id="row-description-0" name="requisition[0][description]" placeholder="Description"></textarea>
            </td>
            <td class="row-quantity-container">
                <input type="number" min="1" class="row-quantity form-control" id="row-quantity-0" name="requisition[0][quantity]" value="1" required>
            </td>
            <td class="row-amount-container">
                <input type="number" min="0" step="0.01" class="row-amount form-control" id="row-amount-0" name="requisition[0][amount]" required>
            </td>
            <td class="row-total-container">
                <input type="number" min="0" class="row-total form-control" id="row-total-0" name="requisition[0][total]" readonly>
            </td>
            <td>
                <button class="btn btn-success add-row" type="button"> + </button>
                <button class="btn btn-danger remove-row" type="button"> - </button>
            </td>
        </tr>
    `;

    let calculateMaterialTotal = () => {
        var total = 0;
        
        $('.row-total').each(function() {
            var val = parseFloat($(this).val()) || 0;
            total += val;
        });

        $('#grand-total-column').html(convertIntoAmount.format(total.toFixed(2)));
    }

    let initializeProducts = (element = null) => {
        if (element) {
            $(element).select2({
                placeholder: 'Select a product',
                width: '100%',
                ajax: {
                    url: "{{ route('product-list') }}",
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
                                    text: item.text,
                                    price: item.price
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
                    $result.attr('data-price', data.price);
                    $result.text(data.text);
                    return $result;
                },
                templateSelection: function (container) {
                    $(container.element).attr("data-price", container.price);
                                            
                    return container.text;
                }
            }).on('change', function () {
                var selectedElement = $(this);
                var selectedProduct = $(this).select2('data')[0];

                if (selectedElement) {
                    selectedProductPrice = parseFloat(selectedProduct?.price) || 0;
                    let qty = parseFloat(selectedElement.parent().parent().find('input.row-quantity')) || 1;

                    selectedElement.parent().parent().find('input.row-amount').val(parseFloat(selectedProductPrice * qty).toFixed(2));
                    selectedElement.parent().parent().find('input.row-total').val(parseFloat(selectedProductPrice * qty).toFixed(2));
                }

                calculateMaterialTotal();
            });
        }
    }

    let initializeType = (element = null) => {
        if (element) {
            $(element).select2({
                placeholder: 'Select type',
                width: '100%',
            }).on('change', function () {
                let nextId = parseInt($(this).attr('id').replace('row-type-', '')) || 0;
                console.log(nextId);
                

                if (nextId >= 0) {
                    if ($('option:selected', this).val() == 'INVENTORY') {
                        $(this).parent().next().html(`
                            <select class="row-product" id="row-product-${nextId}" name="requisition[${nextId}][product]" required>
                            </select>
                        `);

                        initializeProducts(`#row-product-${nextId}`)
                    } else {
                        $(this).parent().next().html(`
                            <select class="row-vendor" id="row-vendor-${nextId}" name="requisition[${nextId}][vendor]" required>
                            </select> <br/> <br/>
                            <input class="row-product form-control" placeholder="Product Name" id="row-product-${nextId}" name="requisition[${nextId}][product]" required>
                        `);

                        initializeVendor(`#row-vendor-${nextId}`)
                    }
                }

            });
        }
    }

    let initializeVendor = (element = null) => {
        if (element) {
            $(element).select2({
                placeholder: 'Select vendor',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: "{{ route('user-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,
                            _token: "{{ csrf_token() }}",
                            expertises: function () {
                                return $('#expertise').val();
                            },
                            roles: ['vendor']
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: $.map(data.items, function(item) {
                                return { id: item.id, text: item.text };
                            }),
                            pagination: { more: data.pagination.more }
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (data.loading) return data.text;
                    var $result = $('<span></span>');
                    $result.text(data.text);
                    return $result;
                }                
            }).on('change', function () {
            });
        }
    }

    $(document).on('input', '.row-amount, .row-quantity', calculateMaterialTotal);
    $(document).on('click', '.add-row, .remove-row', calculateMaterialTotal);

    initializeProducts('.row-product');
    initializeType('.row-type');

    $(document).on('click', '.add-row', function () {
        var rowCount = $('#multi-items tr').length;
        
        var row = $(itemRow);

        row.find('.row-type').attr('id', `row-type-${rowCount}`).attr('name', `requisition[${rowCount}][type]`);
        row.find('.row-product').attr('id', `row-product-${rowCount}`).attr('name', `requisition[${rowCount}][product]`);
        row.find('.row-description').attr('id', `row-description-${rowCount}`).attr('name', `requisition[${rowCount}][description]`);
        row.find('.row-quantity').attr('id', `row-quantity-${rowCount}`).attr('name', `requisition[${rowCount}][quantity]`);
        row.find('.row-amount').attr('id', `row-amount-${rowCount}`).attr('name', `requisition[${rowCount}][amount]`);
        row.find('.row-total').attr('id', `row-total-${rowCount}`).attr('name', `requisition[${rowCount}][total]`);

        initializeProducts(row.find('.row-product'));
        initializeType(row.find('.row-type'));

        $('#multi-items').append(row);

        $(`#row-type-${rowCount}`).rules("add", { required: true });
        $(`#row-product-${rowCount}`).rules("add", { required: true });
        $(`#row-quantity-${rowCount}`).rules("add", { required: true, number: true, min: 1 });
        $(`#row-amount-${rowCount}`).rules("add", { required: true, number: true, min: 0 });
    });

    $(document).on('change', '.row-quantity', function () {
        let quantity = parseFloat($(this).val()) || 0;
        let price = parseFloat($(this).parent().next().find('input.row-amount').val()) || 0;

        $(this).parent().next().next().find('input.row-total').val(parseFloat(quantity * price).toFixed(2));
        calculateMaterialTotal();
    });

    $(document).on('change', '.row-amount', function () {
        let price = parseFloat($(this).val()) || 0;
        let quantity = parseFloat($(this).parent().prev().find('input.row-quantity').val()) || 0;

        $(this).parent().next().find('input.row-total').val(parseFloat(quantity * price).toFixed(2));
        calculateMaterialTotal();
    });

    $(document).on('click', '.remove-row', function () {
        if ($('#multi-items tr').length > 0) {
            let that = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'You will not be able to recover this line item!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $(that).closest('tr').remove();
                    calculateMaterialTotal();
                }
            });
        }
    });    

    $('#job_id').select2({
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

    $('#requisitionForm').validate({
        rules: {
            job_id: { required: true }
        },
        errorPlacement: function(error, element) {
            error.appendTo(element.parent());
        },
        submitHandler: function(form) {
            form.submit();
        }
    });
});
</script>
@endpush 