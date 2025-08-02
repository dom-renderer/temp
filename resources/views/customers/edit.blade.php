@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

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
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Edit Customer</div>
            <div class="card-body">
                <form id="engineerForm" method="POST" action="{{ route('customers.update', encrypt($engineer->id)) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $engineer->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3 row">
                                <div class="col-12">
                                    <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="hidden" name="alternate_dial_code" id="dial_code">                            
                                    <input type="tel" class="form-control @error('alternate_phone_number') is-invalid @enderror" id="phone_number" name="alternate_phone_number" value="{{ old('alternate_phone_number', $engineer->alternate_phone_number) }}" required>
                                    @error('alternate_phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $engineer->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="profile" class="form-label">Profile Image</label>
                                <input type="file" class="form-control @error('profile') is-invalid @enderror" id="profile" name="profile" accept="image/*">
                                @if($engineer->profile)
                                    <img src="{{ asset('storage/users/profile/' . $engineer->profile) }}" alt="Profile" class="img-thumbnail mt-2" width="80">
                                @endif
                                @error('profile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="1" {{ old('status', $engineer->status) == '1' ? 'selected' : '' }}>Enable</option>
                                    <option value="0" {{ old('status', $engineer->status) == '0' ? 'selected' : '' }}>Disable</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="form-text text-muted">Leave blank to keep unchanged.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                                <textarea name="address_line_1" id="address_line_1" class="form-control @error('address_line_1') is-invalid @enderror" required>{{ old('address_line_1', $engineer->address_line_1) }}</textarea>
                                @error('address_line_1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="address_line_2" class="form-label">Address Line 2</label>
                                <textarea name="address_line_2" id="address_line_2" class="form-control @error('address_line_2') is-invalid @enderror">{{ old('address_line_2', $engineer->address_line_2) }}</textarea>
                                @error('address_line_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <select name="country" id="country" class="form-select @error('country') is-invalid @enderror">
                                    <option value="">Select Country</option>
                                </select>
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="state" class="form-label">State</label>
                                <select name="state" id="state" class="form-select @error('state') is-invalid @enderror">
                                    <option value="">Select State</option>
                                </select>
                                @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <select name="city" id="city" class="form-select @error('city') is-invalid @enderror">
                                    <option value="">Select City</option>
                                </select>
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="pincode" class="form-label">Pincode <span class="text-danger">*</span></label>
                                <input type="text" name="pincode" id="pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode', $engineer->pincode) }}" required>
                                @error('pincode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" id="mapButton">
                                    Map (Drop pin on Location)
                                </button>
                                <input type="hidden" name="location_url" id="location_url" value="{{ old('location_url', $engineer->location_url) }}">
                                <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $engineer->latitude) }}">
                                <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $engineer->longitude) }}">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <div id="mapContainer" style="display: none;">
                                <input class="form-control" id="pac-input" type="text" placeholder="Search for a place" />
                                <div id="map" style="height: 400px; width: 100%; margin-top: 10px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script src="{{ asset('assets/js/intel-tel.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>
<script>
$(document).ready(function() {

    const input = document.querySelector('#phone_number');
    const errorMap = ["Phone number is invalid.", "Invalid country code", "Too short", "Too long"];
    const iti = window.intlTelInput(input, {
        initialCountry: "{{ Helper::getIso2ByDialCode($engineer->alternate_dial_code) }}",
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

    $('#country').select2({
        allowClear: true,
        placeholder: 'Select country',
        width: '100%',
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
        }
    });

    $('#state').select2({
        allowClear: true,
        placeholder: 'Select state',
        width: '100%',
        ajax: {
            url: "{{ route('state-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    country_id: $('#country').val(),
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
        }
    });

    $('#city').select2({
        allowClear: true,
        placeholder: 'Select city',
        width: '100%',
        ajax: {
            url: "{{ route('city-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    state_id: $('#state').val(),
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
        }
    });

    @if(isset($engineer->countryr->id))
        $('#country').append(new Option('{{ $engineer->countryr->name ?? '' }}', '{{ $engineer->country }}', true, true));
    @endif
    @if(isset($engineer->stater->id))
        $('#state').append(new Option('{{ $engineer->stater->name ?? '' }}', '{{ $engineer->state }}', true, true));
    @endif
    @if(isset($engineer->cityr->id))
        $('#city').append(new Option('{{ $engineer->cityr->name ?? '' }}', '{{ $engineer->city }}', true, true));
    @endif

    let map, marker, autocomplete;

    $('#mapButton').click(function() {
        $('#mapContainer').toggle();
        if ($('#mapContainer').is(':visible')) {
            initMap();
        }
    });

    function initMap() {
        if (map) return;

        let defaultLocation;
        if ($('#latitude').val() && $('#longitude').val()) {
            defaultLocation = { 
                lat: parseFloat($('#latitude').val()), 
                lng: parseFloat($('#longitude').val()) 
            };
        } else {
            defaultLocation = { lat: 13.174103138553395, lng: -59.55183389025077 };
        }
        
        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 15,
            center: defaultLocation,
        });

        marker = new google.maps.Marker({
            position: defaultLocation,
            map: map,
            draggable: true
        });

        autocomplete = new google.maps.places.Autocomplete(
            document.getElementById('pac-input'),
            { types: ['geocode'] }
        );

        autocomplete.bindTo('bounds', map);

        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (!place.geometry) {
                return;
            }

            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);
            }

            marker.setPosition(place.geometry.location);
            updateLocationFields(place.geometry.location.lat(), place.geometry.location.lng(), place.formatted_address);
        });

        google.maps.event.addListener(marker, 'dragend', function() {
            const position = marker.getPosition();
            const geocoder = new google.maps.Geocoder();
            
            geocoder.geocode({ location: position }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    updateLocationFields(position.lat(), position.lng(), results[0].formatted_address);
                } else {
                    updateLocationFields(position.lat(), position.lng(), '');
                }
            });
        });

        google.maps.event.addListener(map, 'click', function(event) {
            marker.setPosition(event.latLng);
            const geocoder = new google.maps.Geocoder();
            
            geocoder.geocode({ location: event.latLng }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    updateLocationFields(event.latLng.lat(), event.latLng.lng(), results[0].formatted_address);
                } else {
                    updateLocationFields(event.latLng.lat(), event.latLng.lng(), '');
                }
            });
        });
    }

    function updateLocationFields(lat, lng, address) {
        $('#latitude').val(lat);
        $('#longitude').val(lng);
        $('#location_url').val(address);
    }

    $('#engineerForm').validate({
        rules: {
            name: { required: true },
            email: { required: true, email: true },
            alternate_dial_code: { required: true },
            status: { required: true },
            address_line_1: { required: true },
            pincode: { required: true }
        },
        errorPlacement: function(error, element) {
            if (element.attr('id') === 'phone_number') {
                error.insertAfter(element.parent());
            } else {
                error.appendTo(element.parent());
            }
        },
        submitHandler: function (form) {
            $('#dial_code').val(iti.s.dialCode);
            $('body').find('.LoaderSec').removeClass('d-none');
            form.submit();
        }
    });
});
</script>
@endpush 