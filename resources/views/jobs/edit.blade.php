@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle, 'rightSideTitle' => $job->code, 'datepicker' => true])

@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/intel-tel.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">

<style>
    div.iti--inline-dropdown {
		min-width: 100%!important;
	}
	.iti__selected-flag {
		height: 32px!important;
	}
	.iti--show-flags {
		width: 100%!important;
	}  
	label.error {
		color: red;
	}
	#phone_number{
		font-family: "Hind Vadodara",-apple-system,BlinkMacSystemFont,"Segoe UI","Helvetica Neue",Arial,sans-serif;
		font-size: 15px;
	}
    #map {
        height: 400px;
        width: 100%;
        margin-top: 10px;
    }

        .pac-card {
            background-color: #fff;
            border: 0;
            border-radius: 2px;
            box-shadow: 0 1px 4px -1px rgba(0, 0, 0, 0.3);
            margin: 10px;
            padding: 0 0.5em;
            font: 400 18px Roboto, Arial, sans-serif;
            overflow: hidden;
            font-family: Roboto;
            padding: 0;
        }

        .pac-container {
            z-index: 9999!important;
        }

        #pac-container {
            padding-bottom: 12px;
            margin-right: 12px;
            z-index: 999999999;
        }

        .pac-controls {
            display: inline-block;
            padding: 5px 11px;
        }

        .pac-controls label {
            font-family: Roboto;
            font-size: 13px;
            font-weight: 300;
        }

        #pac-input {
            background-color: #fff;
            font-family: Roboto;
            font-size: 15px;
            font-weight: 300;
            text-overflow: ellipsis;
        }

                 #pac-input:focus {
             border-color: #4d90fe;
         }

         .map-search-container {
             margin-bottom: 10px;
         }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">        
        <div class="card">
            <div class="card-header">
                @if ($errors->any())
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('jobs.update', encrypt($job->id)) }}" enctype="multipart/form-data" id="customerForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="customer" class="form-label"> Customer <span class="text-danger">*</span></label>
                        <select name="customer" id="customer" required disabled>
                            <option value="{{ $job->customer_id }}" selected>{{ $job->customer->name }}</option>
                        </select>
                        @error('customer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row mb-2 mt-2">
                        <div class="col-5" style="border-right: 1px solid #0000001f;">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label"> Contact Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name', $job->contact_name) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="customer_email" class="form-label"> Email <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_email" name="customer_email" value="{{ old('customer_email', $job->email) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3 row">
                                <div class="col-12">
                                    <label for="customer_phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label> <br>
                                    <input type="hidden" name="customer_alternate_dial_code" id="customer_dial_code" value="{{ old('customer_alternate_dial_code', $job->contact_dial_code) }}">
                                    <input type="tel" class="form-control" id="customer_phone_number" name="customer_alternate_phone_number" value="{{ old('customer_alternate_phone_number', $job->contact_phone_number) }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="mb-3">
                                <label for="customer_billing_name" class="form-label"> Billing Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_billing_name" name="customer_billing_name" value="{{ old('customer_billing_name', $job->billing_name) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="customer_address_line_1" class="form-label"> Address Line 1 <span class="text-danger">*</span></label>
                                <textarea name="customer_address_line_1" id="customer_address_line_1" class="form-control" required>{{ old('customer_address_line_1', $job->address_line_1) }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="customer_address_line_2" class="form-label"> Address Line 2 </label>
                                <textarea name="customer_address_line_2" id="customer_address_line_2" class="form-control">{{ old('customer_address_line_2', $job->address_line_2) }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" id="mapButton2">
                                    Map (Drop pin on Location)
                                </button>
                                <input type="hidden" name="customer_location_url" id="customer_location_url" value="{{ old('customer_location_url', $job->location_url) }}">
                                <input type="hidden" name="customer_latitude" id="customer_latitude" value="{{ old('customer_latitude', $job->latitude) }}">
                                <input type="hidden" name="customer_longitude" id="customer_longitude" value="{{ old('customer_longitude', $job->longitude) }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="title"> Title <span class="text-danger"> * </span> </label>
                            <input type="text" id="title" name="title" class="form-control" value="{{ old('title', $job->title) }}" required>
                        </div>
                        <div class="col-6">
                            <label for="opening_date"> Job Added Date <span class="text-danger"> * </span> </label>
                            <input type="text" id="opening_date" name="opening_date" class="form-control" value="{{ old('opening_date', \Carbon\Carbon::parse($job->opening_date)->format('d-m-Y')) }}" readonly required>
                        </div>
                        <div class="col-6">
                            <label for="visiting_date"> Visiting Date <span class="text-danger"> * </span> </label>
                            <input type="text" id="visiting_date" name="visiting_date" class="form-control" value="{{ old('visiting_date', \Carbon\Carbon::parse($job->visiting_date)->format('d-m-Y')) }}" readonly required>
                        </div>
                        <div class="col-12 mt-2">
                            <label for="expertise"> Expertise </label>
                            <select name="expertise[]" id="expertise" multiple>
                            @foreach($job->expertise as $expertise)
                                @if(isset($expertise->expertise->id))
                                    <option value="{{ $expertise->expertise->id }}" selected>{{ $expertise->expertise->name }}</option>
                                @endif
                            @endforeach
                            </select>
                        </div>
                        <div class="col-12 mt-2">
                            <label for="technicians"> Technicians <span class="text-danger"> * </span> </label>
                            <select name="technicians[]" id="technicians" multiple>
                                @foreach($job->technicians as $technician)
                                    @if(isset($technician->technician->id))
                                        <option value="{{ $technician->technician->id }}" selected>{{ $technician->technician->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mt-2">
                            <label for="description"> Description <span class="text-danger"> * </span> </label>
                            <textarea name="description" id="description" class="form-control" required>{{ old('description', $job->description) }}</textarea>
                        </div>
                        <div class="col-12 mt-2">
                            <label for="summary"> Summary </label>
                            <textarea name="summary" id="summary" class="form-control">{{ old('summary', $job->summary) }}</textarea>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <h4> Deposit Information </h4>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="requires_deposit" class="form-label"> Requires Deposit </label>
                                <select name="requires_deposit" id="requires_deposit" class="form-select">
                                    <option value="0" {{ $job->requires_deposit ? '' : 'selected' }}>No</option>
                                    <option value="1" {{ $job->requires_deposit ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 deposit-fields" style="display: {{ $job->requires_deposit ? 'block' : 'none' }};">
                            <div class="mb-3">
                                <label for="deposit_type" class="form-label"> Deposit Type </label>
                                <select name="deposit_type" id="deposit_type" class="form-select">
                                    <option value="FIX" {{ $job->deposit_type == 'FIX' ? 'selected' : '' }}>Fixed Amount</option>
                                    <option value="PERCENT" {{ $job->deposit_type == 'PERCENT' ? 'selected' : '' }}>Percentage</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 deposit-fields" style="display: {{ $job->requires_deposit ? 'block' : 'none' }};">
                            <div class="mb-3">
                                <label for="deposit_amount" class="form-label"> Deposit Amount </label>
                                <input type="number" name="deposit_amount" id="deposit_amount" class="form-control" step="0.01" min="0" value="{{ old('deposit_amount', $job->deposit_amount) }}">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <h4> Required Parts & Materials 
                            <button type="button" class="row-add btn btn-success float-end"> Add Item </button>
                        </h4>
                        <div class="col-12 mt-4">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Product</th>
                                        <th>Description</th>
                                        <th width="10%">Quantity</th>
                                        <th width="10%">Price</th>
                                        <th width="10%">Amount</th>
                                        <th width="7%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="materials-container">
                                    @foreach($job->materials as $material)
                                    <tr data-material-id="{{ $material->id }}">
                                        <td>
                                            <select name="material[{{ $material->id }}][category]" class="form-select material-category" required>
                                                @if(isset($material->product->category->id))
                                                    <option value="{{ $material->product->category->id }}" selected>{{ $material->product->category->name }}</option>
                                                @endif
                                            </select>
                                        </td>
                                        <td>
                                            <select name="material[{{ $material->id }}][product]" class="form-select material-product" required>
                                                @if(isset($material->product->id))
                                                    <option value="{{ $material->product->id }}" data-price="{{ $material->product->amount }}" selected>{{ $material->product->name }}</option>
                                                @endif
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="material[{{ $material->id }}][description]" class="form-control" value="{{ $material->description }}">
                                        </td>
                                        <td>
                                            <input type="number" name="material[{{ $material->id }}][quantity]" class="form-control material-quantity" value="{{ $material->quantity }}" min="1" step="1" required>
                                        </td>
                                        <td>
                                            <input type="number" name="material[{{ $material->id }}][price]" class="form-control material-price" value="{{ $material->amount }}" min="0" step="0.01" required>
                                        </td>
                                        <td>
                                            <input type="number" name="material[{{ $material->id }}][amount]" class="form-control material-amount" value="{{ $material->total }}" min="0" step="0.01" required readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-row"> <i class="fa fa-trash"> </i> </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5"> Total </td>
                                        <td colspan="2" id="material-total">
                                            {{ number_format($job->materials->sum('amount'), 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Job</button>
                    <a href="{{ route('jobs.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Customer Map Modal -->
<div class="modal fade" id="customerMapModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="customerMapModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h1 class="modal-title fs-5" id="customerMapModalLabel">Select Customer Location</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="map-search-container">
                <input class="form-control" id="customer-pac-input" type="text" placeholder="Search for a place" />
            </div>
            <div id="customer-map" style="height: 400px; width: 100%;"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="confirmCustomerLocation">Confirm Location</button>
        </div>
    </div>
  </div>
</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script src="{{ asset('assets/js/intel-tel.js') }}"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>
<script>
$(document).ready(function() {

    $('#opening_date, #visiting_date').datepicker({ format: 'dd-mm-yyyy', autoclose: true, dateFormat: 'dd-mm-yy' });
    $('#customer').select2({
        width: '100%'
    });

    $('#expertise').select2({
        placeholder: 'Select an expertise',
        width: '100%',
        ajax: {
            url: "{{ route('expertise-list') }}",
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

    let customerInput = document.querySelector('#customer_phone_number');
    let customerIti = window.intlTelInput(customerInput, {
        initialCountry: "{{  Helper::$defaulDialCode  }}",
        separateDialCode: true,
        nationalMode: false,
        preferredCountries: @json(\App\Models\Country::select('iso2')->pluck('iso2')->toArray()),
        utilsScript: "{{ asset('assets/js/intel-tel-2.min.js') }}"
    });

    customerInput.addEventListener("countrychange", function() {
        if (customerIti.isValidNumber()) {
            $('#customer_dial_code').val(customerIti.s.dialCode);
        }
    });
    customerInput.addEventListener('keyup', () => {
        if (customerIti.isValidNumber()) {
            $('#customer_dial_code').val(customerIti.s.dialCode);
        }
    });

    $('#technicians').select2({
        allowClear: true,
        placeholder: 'Select technicians',
        width: '100%',
        theme: 'classic',
        ajax: {
            url: "{{ route('user-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    roles: ['technician'],
                    expertises: function () {
                        return $('#expertise').val();
                    },
                    _token: "{{ csrf_token() }}"
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
    });

    let customerMap, customerMarker, customerSearchBox;

    $('#mapButton2').on('click', function() {
        if (!customerMap) {
            let lat = $('#customer_latitude').val();
            let long = $('#customer_longitude').val();
            initCustomerMap(lat, long);
        }
        
        $('#customerMapModal').modal('show');
    });

    function initCustomerMap(lat = null, long = null) {
        const defaultLocation = { lat: lat ? parseFloat(lat) : 13.174103138553395, lng: long ? parseFloat(long) : -59.55183389025077 };
        
        customerMap = new google.maps.Map(document.getElementById('customer-map'), {
            zoom: 10,
            center: defaultLocation,
        });

        customerMarker = new google.maps.Marker({
            map: customerMap,
            position: defaultLocation,
            draggable: true
        });

        customerSearchBox = new google.maps.places.SearchBox(document.getElementById('customer-pac-input'));

        customerMap.addListener('bounds_changed', () => {
            customerSearchBox.setBounds(customerMap.getBounds());
        });

        customerSearchBox.addListener('places_changed', () => {
            const places = customerSearchBox.getPlaces();
            if (places.length === 0) return;

            const place = places[0];
            if (!place.geometry || !place.geometry.location) return;

            customerMap.setCenter(place.geometry.location);
            customerMap.setZoom(15);
            customerMarker.setPosition(place.geometry.location);
            updateCustomerLocationData(place.geometry.location, place.formatted_address);
        });

        customerMap.addListener('click', (event) => {
            customerMarker.setPosition(event.latLng);
            updateCustomerLocationData(event.latLng);
        });

        customerMarker.addListener('dragend', (event) => {
            updateCustomerLocationData(event.latLng);
        });
    }

    function updateCustomerLocationData(latLng, address = null) {
        $('#customer_latitude').val(latLng.lat());
        $('#customer_longitude').val(latLng.lng());
        
        if (address) {
            $('#customer_location_url').val(address);
        } else {
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: latLng }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    $('#customer_location_url').val(results[0].formatted_address);
                }
            });
        }
    }

    $('#requires_deposit').on('change', function() {
        if ($(this).val() == '1') {
            $('.deposit-fields').show();
            $('#deposit_type, #deposit_amount').prop('required', true);
        } else {
            $('.deposit-fields').hide();
            $('#deposit_type, #deposit_amount').prop('required', false);
            $('#deposit_amount').val('');
        }
    });

    $('#deposit_type').on('change', function() {
        if ($(this).val() == 'PERCENT') {
            $('#deposit_amount').attr('max', '100');
            $('#deposit_amount').attr('placeholder', 'Enter percentage (0-100)');
        } else {
            $('#deposit_amount').removeAttr('max');
            $('#deposit_amount').attr('placeholder', 'Enter amount');
        }
    });

    let materialRowCount = {{ $job->materials->count() }};

    $(document).on('click', '.row-add', function() {
        materialRowCount++;
        const newRow = `
            <tr data-material-id="new_${materialRowCount}">
                <td>
                    <select name="material[new_${materialRowCount}][category]" class="form-select material-category" required>
                        <option value="">Select Category</option>
                    </select>
                </td>
                <td>
                    <select name="material[new_${materialRowCount}][product]" class="form-select material-product" required>
                        <option value="">Select Product</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="material[new_${materialRowCount}][description]" class="form-control" >
                </td>
                <td>
                    <input type="number" name="material[new_${materialRowCount}][quantity]" class="form-control material-quantity" min="1" step="1" required>
                </td>
                <td>
                    <input type="number" name="material[new_${materialRowCount}][price]" class="form-control material-price" min="0" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="material[new_${materialRowCount}][amount]" class="form-control material-amount" min="0" step="0.01" required readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row"> <i class="fa fa-trash"> </i> </button>
                </td>
            </tr>
        `;
        $('#materials-container').append(newRow);
        
        const newRowElement = $(`tr[data-material-id="new_${materialRowCount}"]`);
        initializeMaterialSelects(newRowElement);
    });

    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        calculateTotal();
    });

    function initializeMaterialSelects(row) {
        row.find('.material-category').select2({
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
                        results: data.items,
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

        row.find('.material-product').select2({
            allowClear: true,
            placeholder: 'Select product',
            width: '100%',
            theme: 'classic',
            ajax: {
                url: "{{ route('product-list') }}",
                type: "POST",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        searchQuery: params.term,
                        page: params.page || 1,  
                        _token: "{{ csrf_token() }}",
                        category: function () {
                            return $(element).parent().prev().find('.input-category option:selected').val()
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
        });
    }

    $('tr[data-material-id]').each(function() {
        initializeMaterialSelects($(this));
    });

    $(document).on('change', '.material-category', function() {
        const row = $(this).closest('tr');
        const productSelect = row.find('.material-product');
        productSelect.val(null).trigger('change');
    });

    $(document).on('input', '.material-quantity, .material-price', function() {
        const row = $(this).closest('tr');
        const quantity = parseFloat(row.find('.material-quantity').val()) || 0;
        const price = parseFloat(row.find('.material-price').val()) || 0;
        const amount = quantity * price;
        row.find('.material-amount').val(amount.toFixed(2));
        calculateTotal();
    });

    function calculateTotal() {
        let total = 0;
        $('.material-amount').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#material-total').text(convertIntoAmount.format(total.toFixed(2)));
    }

    $('#confirmCustomerLocation').on('click', function() {
        $('#customerMapModal').modal('hide');
    });

    $('#customerForm').validate({
        rules: {
            customer: { required: true },
            title: { required: true },
            description: { required: true },
            'technicians[]': { required: true },
            customer_name: { required: true },
            customer_email: { 
                required: true, 
                email: true 
            },
            customer_alternate_phone_number: { required: true },
            customer_billing_name: { required: true },
            customer_address_line_1: { required: true },
            opening_date: { required: true },
            visiting_date: { required: true }
        },
        errorPlacement: function(error, element) {
            if (element.attr('id') === 'customer_phone_number' || element.attr('id') === 'technicians') {
                error.insertAfter(element.parent());
            } else {
                error.appendTo(element.parent());
            }
        },
        submitHandler: function (form) {
            if (customerIti.isValidNumber()) {
                $('#customer_dial_code').val(customerIti.s.dialCode);
            }
            form.submit();
        }
    });

});
</script>
@endpush 