@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle, 'rightSideTitle' => Helper::jobCode(), 'datepicker' => true])

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
                <form method="POST" action="{{ route('jobs.store') }}" enctype="multipart/form-data" id="customerForm">
                    @csrf

                    <div class="mb-3">
                        <label for="customer" class="form-label"> Customer <span class="text-danger">*</span></label>
                        <select name="customer" id="customer" required>

                        </select>
                        @error('customer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row mb-2 mt-2">
                        <div class="col-5" style="border-right: 1px solid #0000001f;">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label"> Contact Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" value="" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="customer_email" class="form-label"> Email <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_email" name="customer_email" value="" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3 row">
                                <div class="col-12">
                                    <label for="customer_phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label> <br>
                                    <input type="hidden" name="customer_alternate_dial_code" id="customer_dial_code">
                                    <input type="tel" class="form-control" id="customer_phone_number" name="customer_alternate_phone_number" value="" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="mb-3">
                                <label for="customer_billing_name" class="form-label"> Billing Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_billing_name" name="customer_billing_name" value="" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="customer_address_line_1" class="form-label"> Address Line 1 <span class="text-danger">*</span></label>
                                <textarea name="customer_address_line_1" id="customer_address_line_1" class="form-control" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="customer_address_line_2" class="form-label"> Address Line 2 </label>
                                <textarea name="customer_address_line_2" id="customer_address_line_2" class="form-control" ></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" id="mapButton2">
                                    Map (Drop pin on Location)
                                </button>
                                <input type="hidden" name="customer_location_url" id="customer_location_url">
                                <input type="hidden" name="customer_latitude" id="customer_latitude">
                                <input type="hidden" name="customer_longitude" id="customer_longitude">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="title"> Title <span class="text-danger"> * </span> </label>
                            <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}" required>
                        </div>
                        <div class="col-6">
                            <label for="opening_date"> Job Added Date <span class="text-danger"> * </span> </label>
                            <input type="text" id="opening_date" name="opening_date" class="form-control" value="{{ old('opening_date', date('d-m-Y')) }}" readonly required>
                        </div>
                        <div class="col-6">
                            <label for="visiting_date"> Visiting Date <span class="text-danger"> * </span> </label>
                            <input type="text" id="visiting_date" name="visiting_date" class="form-control" value="{{ old('visiting_date', date('d-m-Y')) }}" readonly required>
                        </div>
                        <div class="col-12 mt-2">
                            <label for="expertise"> Expertise </label>
                            <select name="expertise[]" id="expertise" multiple></select>
                        </div>
                        <div class="col-12 mt-2">
                            <label for="technicians"> Technicians <span class="text-danger"> * </span> </label>
                            <select name="technicians[]" id="technicians" multiple></select>
                        </div>
                        <div class="col-12 mt-2">
                            <label for="description"> Description <span class="text-danger"> * </span> </label>
                            <textarea name="description" id="description" class="form-control" required></textarea>
                        </div>
                        <div class="col-12 mt-2">
                            <label for="summary"> Summary </label>
                            <textarea name="summary" id="summary" class="form-control"></textarea>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <h4> Deposit Information </h4>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="requires_deposit" class="form-label"> Requires Deposit </label>
                                <select name="requires_deposit" id="requires_deposit" class="form-select">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 deposit-fields" style="display: none;">
                            <div class="mb-3">
                                <label for="deposit_type" class="form-label"> Deposit Type </label>
                                <select name="deposit_type" id="deposit_type" class="form-select">
                                    <option value="FIX">Fixed Amount</option>
                                    <option value="PERCENT">Percentage</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 deposit-fields" style="display: none;">
                            <div class="mb-3">
                                <label for="deposit_amount" class="form-label"> Deposit Amount </label>
                                <input type="number" name="deposit_amount" id="deposit_amount" class="form-control" step="0.01" min="0">
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
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5"> Total </td>
                                        <td colspan="2" id="material-total">
                                            0.00
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Job</button>
                    <a href="{{ route('jobs.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newCustomerAdditionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="newCustomerAdditionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form action="{{ route('customers.store') }}?response_type=json" method="POST" id="engineerForm" enctype="multipart/form-data"> @csrf
        <div class="modal-content">
        <div class="modal-header">
            <h1 class="modal-title fs-5" id="newCustomerAdditionModalLabel">Add Customer</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

            <div class="row mb-2">
                <div class="col-md-5" style="border-right: 1px solid #0000001f;">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="alternate_name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="alternate_name" name="alternate_name" value="" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-12">
                            <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label> <br>
                            <input type="hidden" name="alternate_dial_code" id="dial_code">
                            <input type="tel" class="form-control" id="phone_number" name="alternate_phone_number" value="" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="profile" class="form-label">Profile Image</label>
                        <input type="file" class="form-control" id="profile" name="profile" accept="image/*">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="1" >Enable</option>
                            <option value="0" >Disable</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback"></div>
                    </div> 
                </div>
                <div class="col-md-7">
                    <div class="mb-3">
                        <label for="address_line_1" class="form-label"> Address Line 1 <span class="text-danger">*</span></label>
                        <textarea name="address_line_1" id="address_line_1" class="form-control" required></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="address_line_2" class="form-label"> Address Line 2 </label>
                        <textarea name="address_line_2" id="address_line_2" class="form-control"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="country" class="form-label"> Country </label>
                        <select name="country" id="country" required></select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="state" class="form-label"> State </label>
                        <select name="state" id="state" required></select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label"> City </label>
                        <select name="city" id="city" required></select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="pincode" class="form-label"> Pincode <div class="text-danger"> * </div> </label>
                        <input type="text" name="pincode" id="pincode" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" id="mapButton">
                            Map (Drop pin on Location)
                        </button>
                        <input type="hidden" name="location_url" id="location_url">
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-12">
                    <div id="mapContainer" style="display: none;">
                        <input class="form-control" id="pac-input" type="text" placeholder="Search for a place" />
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary"> Save </button>
        </div>
        </div>
    </form>
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

    $('#opening_date, #visiting_date').datepicker({ format: 'dd-mm-yyyy', autoclose: true, dateFormat: 'dd-mm-yy' });

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

    let customerMap, customerMarker, customerSearchBox;

    $('#mapButton2').on('click', function() {
        if (!customerMap) {
            let lat = $('#customer_latitude').val();
            let long = $('#customer_longitude').val();
            initCustomerMap(lat,long);
        }
        
        $('#customerMapModal').modal('show');
    });

    function initCustomerMap(lat = null, long = null) {
        const defaultLocation = { lat: lat ? lat : 13.174103138553395, lng: long ? long : -59.55183389025077 };
        
        customerMap = new google.maps.Map(document.getElementById('customer-map'), {
            zoom: 10,
            center: defaultLocation,
        });

        customerMarker = new google.maps.Marker({
            map: customerMap,
            draggable: true,
            position: defaultLocation
        });

        const mapInput = document.getElementById('customer-pac-input');
        customerSearchBox = new google.maps.places.SearchBox(mapInput);

        customerMap.addListener('bounds_changed', () => {
            customerSearchBox.setBounds(customerMap.getBounds());
        });

        customerSearchBox.addListener('places_changed', () => {
            const places = customerSearchBox.getPlaces();

            if (places.length === 0) {
                return;
            }

            const place = places[0];

            if (!place.geometry || !place.geometry.location) {
                window.alert("No details available for input: '" + place.name + "'");
                return;
            }

            if (place.geometry.viewport) {
                customerMap.fitBounds(place.geometry.viewport);
            } else {
                customerMap.setCenter(place.geometry.location);
                customerMap.setZoom(17);
            }

            customerMarker.setPosition(place.geometry.location);
            updateCustomerLocationData(place.geometry.location.lat(), place.geometry.location.lng(), place.url);
        });

        customerMap.addListener('click', (event) => {
            customerMarker.setPosition(event.latLng);
            updateCustomerLocationData(event.latLng.lat(), event.latLng.lng());
        });

        customerMarker.addListener('dragend', () => {
            const position = customerMarker.getPosition();
            updateCustomerLocationData(position.lat(), position.lng());
        });
    }

    function updateCustomerLocationData(lat, lng, url = null) {
        $('#customer_latitude').val(lat);
        $('#customer_longitude').val(lng);
        if (url) {
            $('#customer_location_url').val(url);
        }
    }

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
            customer_address_line_1: { required: true }
        },
        errorPlacement: function(error, element) {
            if (element.attr('id') === 'customer_phone_number' || element.attr('id') === 'technicians') {
                error.insertAfter(element.parent());
            } else {
                error.appendTo(element.parent());
            }
        },
        submitHandler: function(form) {
            if (customerIti && customerIti.isValidNumber()) {
                $('#customer_dial_code').val(customerIti.s.dialCode);
            }
            return true;
        }
    });

    let materialRow = `
    <tr>
        <td class="category-container">
            <select class="input-category" id="input-category-0" required></select>
        </td>
        <td class="product-container">
            <select class="input-product" id="input-product-0" name="material[0][product]" required></select>
        </td>
        <td>
            <textarea class="input-description form-control" id="input-description-0" name="material[0][description]"></textarea>
        </td>
        <td>
            <input type="number" min="1" class="input-quantity form-control" id="input-quantity-0" name="material[0][quantity]" value="1" required>
        </td>
        <td>
            <input type="number" min="0" class="input-price form-control" id="input-price-0" name="material[0][price]" readonly>
        </td>
        <td>
            <input type="number" min="0" class="input-amount form-control" id="input-amount-0" name="material[0][amount]" readonly>
        </td>
        <td class="add-remove-container">
            <button type="button" class="btn btn-success row-add"> + </button>
            <button type="button" class="btn btn-danger row-remove"> - </button>
        </td>
    </tr>
    `;

    $(document).on('click', '.row-add', function () {
        var rowCount = $('#materials-container tr').length;
        
        var row = $(materialRow);

        row.find('.input-category').attr('id', `input-category-${rowCount}`);
        row.find('.input-product').attr('id', `input-product-${rowCount}`).attr('name', `material[${rowCount}][product]`);
        row.find('.input-description').attr('id', `input-description-${rowCount}`).attr('name', `material[${rowCount}][description]`);
        row.find('.input-quantity').attr('id', `input-quantity-${rowCount}`).attr('name', `material[${rowCount}][quantity]`);
        row.find('.input-price').attr('id', `input-price-${rowCount}`).attr('name', `material[${rowCount}][price]`);
        row.find('.input-amount').attr('id', `input-amount-${rowCount}`).attr('name', `material[${rowCount}][amount]`);

        initializeCategories(row.find('.input-category'))
        initializeProducts(row.find('.input-product'))

        $('#materials-container').append(row);
    });

    $(document).on('change', '.input-quantity', function () {
        let quantity = parseFloat($(this).val()) || 0;
        let price = parseFloat($(this).parent().next().find('input.input-price').val()) || 0;

        $(this).parent().next().next().find('input.input-amount').val(parseFloat(quantity * price).toFixed(2));
        calculateMaterialTotal();
    });

    $(document).on('click', '.row-remove', function () {
        if ($('#materials-container tr').length > 0) {
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

    let calculateMaterialTotal = () => {
        var total = 0;
        $('.input-amount').each(function() {
            var val = parseFloat($(this).val()) || 0;
            total += val;
        });

        $('#material-total').html(convertIntoAmount.format(total.toFixed(2)));
    }

    let initializeCategories = (element = null) => {
        if (element) {
            $(element).select2({
                placeholder: 'Select a category',
                width: '100%',
                ajax: {
                    url: "{{ route('category-list') }}",
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
        }
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
            }).on('change', function () {
                var selectedElement = $(this);
                var selectedProduct = $(this).select2('data')[0];

                if (selectedElement) {
                    selectedProductPrice = parseFloat(selectedProduct?.price) || 0;
                    let qty = parseFloat(selectedElement.parent().parent().find('input.input-quantity')) || 1;

                    selectedElement.parent().parent().find('input.input-price').val(parseFloat(selectedProductPrice * qty).toFixed(2));
                    selectedElement.parent().parent().find('input.input-amount').val(parseFloat(selectedProductPrice * qty).toFixed(2));
                }

                calculateMaterialTotal();
            });
        }
    }    

    $(document).on('input', '.input-quantity', calculateMaterialTotal);
    $(document).on('click', '.row-add, .row-remove', calculateMaterialTotal);

    let input = document.querySelector('#phone_number');
    const errorMap = ["Phone number is invalid.", "Invalid country code", "Too short", "Too long"];

    let iti = window.intlTelInput(input, {
        initialCountry: "{{  Helper::$defaulDialCode  }}",
        separateDialCode:true,
        nationalMode:false,
        preferredCountries: @json(\App\Models\Country::select('iso2')->pluck('iso2')->toArray()),
        utilsScript: "{{ asset('assets/js/intel-tel-2.min.js') }}"
    });

    input.addEventListener("countrychange", function() {
        if (iti.isValidNumber()) {
            $('#dial_code').val(iti.s.dialCode);
        }
    });
    input.addEventListener('keyup', () => {
        if (iti.isValidNumber()) {
            $('#dial_code').val(iti.s.dialCode);
        }
    });    

    let map, marker, searchBox;
    
    $('#mapButton').on('click', function() {
        $('#mapContainer').toggle();
        if ($('#mapContainer').is(':visible') && !map) {
            initMap();
        }
    });

    function initMap() {
        const defaultLocation = { lat: 13.174103138553395, lng: -59.55183389025077 };
        
        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 10,
            center: defaultLocation,
        });

        marker = new google.maps.Marker({
            map: map,
            draggable: true,
            position: defaultLocation
        });

        const mapContainer = document.getElementById('mapContainer');

        let mapInput = document.getElementById('pac-input');
        mapInput.style.cssText = `
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
        `;

        searchBox = new google.maps.places.SearchBox(mapInput);

        map.addListener('bounds_changed', () => {
            searchBox.setBounds(map.getBounds());
        });

        searchBox.addListener('places_changed', () => {
            const places = searchBox.getPlaces();

            if (places.length === 0) {
                return;
            }

            const place = places[0];

            if (!place.geometry || !place.geometry.location) {
                window.alert("No details available for input: '" + place.name + "'");
                return;
            }

            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);
            }

            marker.setPosition(place.geometry.location);
            updateLocationData(place.geometry.location.lat(), place.geometry.location.lng(), place.url);
        });

        map.addListener('click', (event) => {
            marker.setPosition(event.latLng);
            updateLocationData(event.latLng.lat(), event.latLng.lng());
        });

        marker.addListener('dragend', () => {
            const position = marker.getPosition();
            updateLocationData(position.lat(), position.lng());
        });
    }

    function updateLocationData(lat, lng, url = null) {
        $('#latitude').val(lat);
        $('#longitude').val(lng);
        if (url) {
            $('#location_url').val(url);
        }
    }

    $(document).on('shown.bs.modal', '#newCustomerAdditionModal', function (e) {
        if (e.namespace == 'bs.modal') {
            let currentTarget = $(e.currentTarget);
        }
    });

    $('#technicians').select2({
        placeholder: 'Select technicians',
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
                    roles: ['technician']
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
    }).on('change', function() {

    });

    $('#country').select2({
        placeholder: 'Select State',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#newCustomerAdditionModal'),
        ajax: {
            url: "{{ route('country-list') }}",
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
    }).on('change', function() {
        $('#state').val(null).trigger('change');
        $('#city').val(null).trigger('change');
    });

    $('#state').select2({
        placeholder: 'Select State',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#newCustomerAdditionModal'),
        ajax: {
            url: "{{ route('state-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    _token: "{{ csrf_token() }}",
                    country_id: function() { return $('#country').val(); }
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
    }).on('change', function() {
        $('#city').val(null).trigger('change');
    });

    $('#city').select2({
        placeholder: 'Select City',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#newCustomerAdditionModal'),
        ajax: {
            url: "{{ route('city-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    _token: "{{ csrf_token() }}",
                    state_id: function() { return $('#state').val(); }
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

    $('#customer').select2({
        allowClear: true,
        placeholder: 'Select customer',
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
                    addNewOption: 1,
                    roles: ['customer'],
                    includeUserData: true
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;

                return {
                    results: $.map(data.items, function(item) {
                        return {
                            id: item.id,
                            text: item.text,
                            user: item.user || null,
                            alternate_dial_code_iso: item.alternate_dial_code_iso || null
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
        if ($('option:selected', this).val() == 'ADD_NEW_USER') {
            $('#newCustomerAdditionModal').modal('show');
        }

        const selectedOption = $(this).find('option:selected');
        const userData = $(this).select2('data')[0]?.user;
        const alternaticeIso = $(this).select2('data')[0]?.alternate_dial_code_iso;        
            
        if (userData) {
            $('#customer_name').val(userData.name || '');
            $('#customer_email').val(userData.email || '');
            
            if (alternaticeIso && userData.alternate_phone_number) {
                customerIti.setCountry(alternaticeIso);
                customerInput.value = userData.alternate_phone_number;
                $('#customer_dial_code').val(userData.alternate_dial_code);
            }
            
            let fullAddress = userData.address_line_1 || '';
            if (userData.address_line_2) {
                fullAddress += (fullAddress ? ', ' : '') + userData.address_line_2;
            }
            $('#customer_address_line_1').val(fullAddress);
            
            $('#customer_location_url').val(userData.location_url || '');
            $('#customer_latitude').val(userData.latitude || '');
            $('#customer_longitude').val(userData.longitude || '');
            
            if (userData.latitude && userData.longitude) {
                if (customerMap) {
                    const userLocation = { 
                        lat: parseFloat(userData.latitude), 
                        lng: parseFloat(userData.longitude) 
                    };
                    customerMap.setCenter(userLocation);
                    customerMap.setZoom(15);
                    customerMarker.setPosition(userLocation);
                }
            }
        } else {
            $('#customer_name').val('');
            $('#customer_email').val('');
            customerInput.value = '';
            $('#customer_dial_code').val('');
            $('#customer_address_line_1').val('');
            $('#customer_address_line_2').val('');
            $('#customer_location_url').val('');
            $('#customer_latitude').val('');
            $('#customer_longitude').val('');
        }        
    });

    $('#confirmCustomerLocation').on('click', function() {
        $('#customerMapModal').modal('hide');
    });

    // Deposit functionality
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

    $('#engineerForm').validate({
        rules: {
            name: { required: true },
            email: { required: true, email: true },
            alternate_dial_code: { required: true },
            status: { required: true },
            password: { required: true }
        },
        errorPlacement: function(error, element) {
            if (element.attr('id') === 'phone_number' || element.attr('id') === 'country' || element.attr('id') === 'state' || element.attr('id') === 'city') {
                error.insertAfter(element.parent());
            } else {
                error.appendTo(element.parent());
            }
        },
        submitHandler: function (form) {
            if (iti) {
                $('#dial_code').val(iti.s.dialCode);
            }

            let formData = new FormData(form);

            $.ajax({
                url: "{{ route('customers.store') }}?response_type=json",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('body').find('.LoaderSec').removeClass('d-none');
                },
                success: function(response) {
                    if (response.status) {
                        Swal.fire('Success', 'Customer added successfully!', 'success');
                        $('#newCustomerAdditionModal').modal('hide');

                        if (response.user.id) {
                            $('#customer').html(`<option value="${response.user.id}" selected> ${response.user.name} </option>`).val(response.user.id).trigger('change');
                            $('#customer_name').val(response.user.name);
                            $('#customer_email').val(response.user.email);
                            $('#customer_billing_name').val(response.user.name);
                            $('#customer_address_line_1').val(response.user.address_line_1);
                            $('#customer_address_line_2').val(response.user.address_line_2);
                            $('#customer_location_url').val(response.user.location_url);
                            $('#customer_latitude').val(parseFloat(response.user.latitude) || 13.174103138553395);
                            $('#customer_longitude').val(parseFloat(response.user.longitude) || -59.55183389025077);

                            let alternaticeIso = response.alternate_dial_code_iso || 'in';

                            if (alternaticeIso && response.user.alternate_phone_number) {
                                customerIti.setCountry(alternaticeIso);
                                customerInput.value = response.user.alternate_phone_number;
                                $('#customer_dial_code').val(response.user.alternate_dial_code);
                            }
                        }

                    } else {
                        Swal.fire('Error', 'Something went wrong!', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        $('.invalid-feedback').empty();
                        $('.form-control, .form-select').removeClass('is-invalid');
                        
                        $.each(errors, function(field, messages) {
                            const input = $(`[name="${field}"]`);
                            const feedback = input.siblings('.invalid-feedback');
                            
                            input.addClass('is-invalid');
                            feedback.html(messages.join('<br>'));
                        });
                    } else {
                        Swal.fire('Error', 'Something went wrong', 'error');
                    }
                },
                complete: function () {
                    $('body').find('.LoaderSec').addClass('d-none');
                }
            });
        }
    });

});
</script>
@endpush 