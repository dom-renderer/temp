@extends('layouts.app-master')

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/css/jquery.datetimepicker.css') }}">
     <link href="{{ asset('assets/css/skip-style.css') }}" rel="stylesheet" />

    <style>
        .div-white {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: -4px 4px 20px 5px rgb(0 0 0 / 10%);
}
        label.error {
            color: #af0e0e;            
        }

        input:read-only {
            background: #e9ecef;
            cursor: not-allowed;
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

        .no-active-datepicker-only-timepicker {
            height: 250px!important;
            width: 80px!important;
        }

        #grand-zero-total {
            text-decoration: line-through;
            @if(!$rental->has_zero_total)
            display: none;
            @endif
            position: relative;
        }

        #grand-zero-total::after {
            content: " 0.00";
            text-decoration: none;
            color: inherit;
            position: absolute;
            white-space: nowrap;
            padding-left: 15px;
        }
    </style>
@endpush

@section('content')
    <div class="bg-light p-4 rounded">
        <div class="mt-4">
            <form method="POST" class="skip-edit-form" action="{{ route('rentals.update', $id) }}" enctype="multipart/form-data" id="mainForm" autocomplete="off"> @csrf @method('PUT')


                <div class="row">
                    <div class="col-8">

                        <div class="mb-3 col-12">
                            <label for="code" class="form-label">Code <span class="text-danger"> * </span> </label>
                            <input type="text" name="code" placeholder="Code" id="code" class="form-control text-uppercase" value="{{ $rental->code }}" disabled>
                        </div>

                        <div class="mb-3 col-12">
                            <label for="orderDate" class="form-label"> Order Date <span class="text-danger"> * </span> </label>
                            <input type="text" name="date" class="form-control" id="orderDate" value="{{ date('d-m-Y H:i', strtotime($rental->date)) }}" disabled>
                        </div>

                        <div class="mb-3 col-12">
                            <label for="contracttype" class="form-label"> Contract Type <span class="text-danger"> * </span> </label>
                            @if($rental->contract_type == '0')
                                <input type="text" class="form-control" value="RENTAL" disabled>
                            @else
                                <input type="text" class="form-control" value="SERVICE" disabled>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="customer" class="form-label"> Customer <span class="text-danger"> * </span> </label>
                            <input type="text" class="form-control" value="@if(isset($rental->customer->id)) {{ $rental->customer->name }} - {{ $rental->customer->code }} @endif" disabled>
                        </div>

                        <div class="mb-3 col-12">
                            <label for="location" class="form-label"> Location <span class="text-danger"> * </span> </label>
                            <textarea id="fetch-location" class="form-control" style="margin-top:10px;" disabled>@if(isset($rental->location->id)) {{ $rental->location->address }} @endif</textarea>
                        </div>

                        <div class="mb-3 col-12">
                            <label for="request_by" class="form-label"> Requested By </label>
                            <input type="text" class="form-control" name="request_by" value=" {{ old('request_by', $rental->requested_by) }} ">
                        </div>

                        <div class="mb-3 col-12">
                            <label for="request_method" class="form-label"> Requested Method <span class="text-danger"> * </span> </label>
                            <select name="request_method" id="request_method">
                                @if(isset($rental->reqmethod->id))
                                    <option value="{{ $rental->reqmethod->id }}"> {{ $rental->reqmethod->description }} - {{ $rental->reqmethod->code }} </option>
                                @endif
                            </select>

                            @if ($errors->has('request_method'))
                                <span class="text-danger text-left">{{ $errors->first('request_method') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="po" class="form-label"> Purchase Order </label>
                            <input type="text" name="po" id="po" class="form-control" placeholder="Purchase Order" value="{{ $rental->purchase_order }}">
                        </div>

                        <div class="mb-3 col-12">
                            <label for="skip" class="form-label"> Skip <span class="text-danger"> * </span> </label>
                            <select name="skip" id="skip">
                                @if(isset($rental->inventory->id))
                                    <option value="{{ $rental->inventory->id }}"> {{ $rental->inventory->title }} - {{ $rental->inventory->code }} </option>
                                @endif
                            </select>

                            @if ($errors->has('skip'))
                                <span class="text-danger text-left">{{ $errors->first('skip') }}</span>
                            @endif
                        </div>

                        <div class="for-rental">
                            <div class="mb-3 col-12 d-none">
                                <label for="vehicle" class="form-label"> Vehicle </label>
                                <select name="vehicle" id="vehicle">
                                    @if(isset($rental->vehicle->id))
                                        <option value="{{ $rental->vehicle->id }}"> {{ $rental->vehicle->name }} - {{ $rental->vehicle->code }} </option>
                                    @endif                                    
                                </select>
                            </div>
                        </div>

                        <div class="for-rental">
                            <div class="mb-3 col-12">
                                <label for="driver_id" class="form-label"> Driver </label>
                                <select name="driver_id" id="driver_id">
                                    @if(isset($rental->driveruser->id))
                                        <option value="{{ $rental->driveruser->id }}"> {{ $rental->driveruser->name }} - {{ $rental->driveruser->code }} </option>
                                    @endif                                    
                                </select>
                            </div>
                        </div>

                        <div class="for-rental">
                            <div class="mb-3 col-12 d-none">
                                <label for="second_man_id" class="form-label"> Second Man </label>
                                <select name="second_man_id" id="second_man_id">
                                    @if(isset($rental->smuser->id))
                                        <option value="{{ $rental->smuser->id }}"> {{ $rental->smuser->name }} - {{ $rental->smuser->code }} </option>
                                    @endif                                    
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 col-12">
                            <label for="reference" class="form-label"> Reference </label>
                            <input type="text" name="reference" id="reference" class="form-control" placeholder="Reference" value="{{ $rental->reference }}">
                        </div>

                        <div class="mb-3 col-12">
                            <label for="price_levels" class="form-label"> Price Levels </label>
                            <select name="price_levels" id="price_levels">
                                @if(isset($rental->pricelevel->id))
                                    <option value="{{ $rental->pricelevel->id }}"> {{ $rental->pricelevel->title }} - {{ $rental->pricelevel->code }} </option>
                                @endif
                            </select>
                            <input type="hidden" name="price_level_rate_id" id="price_level_rate_id" value="{{ $rental->price_level_rate_id }}">

                            @if ($errors->has('price_levels'))
                                <span class="text-danger text-left">{{ $errors->first('price_levels') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="waste_type" class="form-label"> Waste Type <span class="text-danger"> * </span> </label>
                            <select name="waste_type" id="waste_type">
                                @if(isset($rental->wastetype->id))
                                    <option value="{{ $rental->wastetype->id }}"> {{ $rental->wastetype->description }} - {{ $rental->wastetype->code }} </option>
                                @endif
                            </select>

                            @if ($errors->has('waste_type'))
                                <span class="text-danger text-left">{{ $errors->first('waste_type') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="company" class="form-label"> Company <span class="text-danger"> * </span> </label>
                            <select name="company" id="company">
                                @if(isset($rental->company->id))
                                    <option value="{{ $rental->company->id }}"> {{ $rental->company->name }} - {{ $rental->company->code }} </option>
                                @endif
                            </select>

                            @if ($errors->has('company'))
                                <span class="text-danger text-left">{{ $errors->first('company') }}</span>
                            @endif
                        </div>

                        <div class="row">
                            <div class="mb-3 col-6 nopadding-left">
                                <div class="form-check form-switch">
                                    @if($newRentalCompleted)
                                        <input type="checkbox" class="form-check-input" role="switch" value="1" @if($rental->frequency_contract == 1) checked @endif disabled>
                                        <input type="hidden" name="frequency_contract" @if($rental->frequency_contract == 1) value="1" @else value="0" @endif>
                                    @else
                                        <input type="checkbox" class="form-check-input" id="isfreq" name="frequency_contract" role="switch" value="1" @if($rental->frequency_contract == 1) checked @endif>
                                    @endif
                                    <label class="form-check-label" for="isfreq"> Schedule </label>
                                </div>
                            </div>

                            <div class="mb-3 col-6 nopadding-right" id="container-for-perpetual">
                                <div class="form-check form-switch">
                                    @if($newRentalCompleted)
                                        <input type="checkbox" class="form-check-input" role="switch" @if($rental->perpatual == 1) checked @endif disabled>
                                        <input type="hidden" name="perpatual" @if($rental->perpatual == 1) value="1" @else value="0" @endif>
                                    @else
                                        <input type="checkbox" name="perpatual" id="perpatual" value="1" class="form-check-input" role="switch" @if($rental->perpatual == 1) checked @endif @if($newRentalCompleted) readonly @endif>
                                    @endif
                                    <label class="form-check-label" for="perpatual">Perpetual</label>
                                </div>
                            </div>
                        </div>                        

                        <div class="main-container-for-frequency border border-secondary pt-4" style="border-radius:15px;margin-bottom:10px;">
                            <div class="row mb-3 col-12">

                                <div class="col-6">
                                    <label for="from" class="form-label"> <span id="start-date-from-label"> @if($rental->contract_type) Service Start Date @else Start Date @endif </span> <span class="text-danger"> * </span> </label>
                                    <input type="text" class="form-control" name="from" value="{{ old('from', date('d-m-Y H:i', strtotime($rental->from_date))) }}" id="from" @if($newRentalCompleted) readonly @endif >
            
                                    @if ($errors->has('from'))
                                        <span class="text-danger text-left">{{ $errors->first('from') }}</span>
                                    @endif
                                </div>
    
                                <div class="col-6 hidable-endate @if($rental->perpatual == 1) invisible @endif ">
                                    <label for="to" class="form-label"> <span id="end-date-to-label"> @if($rental->contract_type) Service End Date @else End Date @endif </span> <span class="text-danger"> * </span> </label>
                                    <input type="text" class="form-control" name="to" id="to" value="{{ old('from', date('d-m-Y H:i', strtotime($rental->to_date))) }}">
            
                                    @if ($errors->has('to'))
                                        <span class="text-danger text-left">{{ $errors->first('to') }}</span>
                                    @endif
                                </div>
    
                            </div>
    
                            <div class="row mb-3 col-12 nopadding-left  @if($rental->contract_type) d-none @endif" id="container-for-rental-service-date">
    
                                <div class="col-6" id="container-for-new-rental">
                                    <label for="deliver" class="form-label"> New Rental <span class="text-danger"> * </span> </label>
                                    <input type="text" class="form-control" name="deliver" value="{{ old('deliver', date('d-m-Y H:i', strtotime($rental->delivery_date))) }}" id="deliver" @if($newRentalCompleted) readonly @endif>
            
                                    @if ($errors->has('deliver'))
                                        <span class="text-danger text-left">{{ $errors->first('deliver') }}</span>
                                    @endif
                                </div>
    
                                <div class="col-6" id="container-for-first-removal">
                                    <label for="removal" class="form-label"> First Removal <span class="text-danger"> * </span> </label>
                                    <input type="text" class="form-control" name="removal" value="{{ old('removal', date('d-m-Y H:i', strtotime($rental->removal_date))) }}" id="removal" @if($firstRemovalCompleted) readonly @endif>
            
                                    @if ($errors->has('removal'))
                                        <span class="text-danger text-left">{{ $errors->first('removal') }}</span>
                                    @endif
                                </div>
    
                            </div>
    
                            <div class="row mb-3 col-12" id="container-for-frequency">

                                <div class="col-6">
                                    <label class="form-label"> Frequency <span class="text-danger"> * </span> </label>
    
                                    @php 
                                        $weekDays = explode(',', $rental->days);
                                    @endphp
    
                                    <div class="show-only-days @if(!$rental->is_specific_day_frequency) d-none @endif">
                                        <input type="checkbox" id="monday" data-week="1" name="select_days[]" value="monday" style="height:15px;width:15px;margin-top:10px;" @if(in_array('monday', $weekDays)) checked @endif>
                                        <label for="monday" class="form-label" style="position:relative;bottom:1px;left:5px;"> Monday </label> <br>
        
                                        <input type="checkbox" id="tuesday" data-week="2" name="select_days[]" value="tuesday" style="height:15px;width:15px;margin-top:10px;" @if(in_array('tuesday', $weekDays)) checked @endif>
                                        <label for="tuesday" class="form-label" style="position:relative;bottom:1px;left:5px;"> Tuesday </label> <br>
        
                                        <input type="checkbox" id="wednesday" data-week="3" name="select_days[]" value="wednesday" style="height:15px;width:15px;margin-top:10px;" @if(in_array('wednesday', $weekDays)) checked @endif>
                                        <label for="wednesday" class="form-label" style="position:relative;bottom:1px;left:5px;"> Wednesday </label> <br>
        
                                        <input type="checkbox" id="thursday" data-week="4" name="select_days[]" value="thursday" style="height:15px;width:15px;margin-top:10px;" @if(in_array('thursday', $weekDays)) checked @endif>
                                        <label for="thursday" class="form-label" style="position:relative;bottom:1px;left:5px;"> Thursday </label> <br>
        
                                        <input type="checkbox" id="friday" data-week="5" name="select_days[]" value="friday" style="height:15px;width:15px;margin-top:10px;" @if(in_array('friday', $weekDays)) checked @endif>
                                        <label for="friday" class="form-label" style="position:relative;bottom:1px;left:5px;"> Friday </label> <br>
        
                                        <input type="checkbox" id="saturday" data-week="6" name="select_days[]" value="saturday" style="height:15px;width:15px;margin-top:10px;" @if(in_array('saturday', $weekDays)) checked @endif>
                                        <label for="saturday" class="form-label" style="position:relative;bottom:1px;left:5px;"> Saturday </label> <br>
        
                                        <input type="checkbox" id="sunday" data-week="0" name="select_days[]" value="sunday" style="height:15px;width:15px;margin-top:10px;" @if(in_array('sunday', $weekDays)) checked @endif>
                                        <label for="sunday" class="form-label" style="position:relative;bottom:1px;left:5px;"> Sunday </label>
                                    </div>
    
                                    <div class="show-only-frequency @if($rental->is_specific_day_frequency) d-none @endif">
                                        <select name="frequency" id="frequency">
                                            <option value="1" @if($rental->frequency_type == '1') selected @endif > Daily </option>
                                            <option value="2" @if($rental->frequency_type == '2') selected @endif > Weekly </option>
                                            <option value="3" @if($rental->frequency_type == '3') selected @endif > Biweekly </option>
                                            <option value="4" @if($rental->frequency_type == '4') selected @endif > Monthly </option>
                                            <option value="5" @if($rental->frequency_type == '5') selected @endif > Bimonthly </option>
                                            <option value="6" @if($rental->frequency_type == '6') selected @endif > Quarterly </option>
                                            <option value="7" @if($rental->frequency_type == '7') selected @endif > Semi Annual </option>
                                            <option value="8" @if($rental->frequency_type == '8') selected @endif > Annual </option>
                                        </select>
                                    </div>
    
                                    <input type="checkbox" value="1" name="frequency_type" id="check_days" style="height:15px;width:15px;margin-top:10px;" @if($rental->is_specific_day_frequency) checked @endif>
                                    <label for="check_days" class="form-label" style="position:relative;bottom:1px;left:5px;"> Select Days </label>
                                </div>
    
                                <div class="col-6">
                                    <label for="removal" class="form-label"> Time <span class="text-danger"> * </span> </label>
                                    <input type="text" name="frequency_time" id="frequency_time" class="form-control" value="{{ date('H:i', strtotime($rental->frequency_time)) }}">
                                </div>
    
                            </div>
                        </div>


                        <div class="mb-3 col-12">
                            <label for="instruction" class="form-label"> Instruction </label>
                            <textarea name="instruction" class="form-control">{{ old('instruction', $rental->instructions) }}</textarea>
                        </div>

                    </div>

                    <div class="col-4">
                        <div class="div-white">
                        <div class="mb-3 col-12">
                            <label for="code" class="form-label"> Payment Term <span class="text-danger"> * </span> </label>
                            <select name="pt" id="pt">
                                @if(isset($rental->paymentterm->id))
                                    <option value="{{ $rental->paymentterm->id }}"> {{ $rental->paymentterm->name }} - {{ $rental->paymentterm->code }} </option>
                                @endif
                            </select>

                            @if ($errors->has('pt'))
                                <span class="text-danger text-left">{{ $errors->first('pt') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="cb" class="form-label"> Charge Basis <strong class="show-on-perp @if($rental->perpatual != 1) invisible @endif"> &nbsp;&nbsp; (UPTO MONTH END  / PERPETUAL) </strong> </label>
                            <input type="text" name="cb" placeholder="Charge Basis" id="cb" value="{{ $rental->charge_basis }}" class="form-control text-uppercase" readonly>
                        </div>                        

                        <div class="mb-3 col-12 d-none">
                            <label for="cb" class="form-label"> Charge Basis Count <strong class="show-on-perp @if($rental->perpatual != 1) invisible @endif"> &nbsp;&nbsp; (UPTO MONTH END  / PERPETUAL) </strong> </label>
                            <input type="text" id="cbcd" class="form-control text-uppercase" value="{{ $rental->charge_basis_count . ' ' . $rental->charge_basis }}" disabled>
                            <input type="hidden" name="cbc" id="cbc" value="{{ $rental->charge_basis_count }}" />
                        </div>

                        <div class="mb-3 col-12">
                            <label for="rental_rate" class="form-label"> Rental Rate <span class="text-danger"> * </span> </label>
                            <input type="number" step="0.01" min="0" name="rental_rate" placeholder="@if($rental->contract_type == '0') Rental Rate @else Servce Rate @endif" id="rental_rate" class="form-control" value="{{ old('rental_rate', $rental->rental) }}" readonly>

                            @if ($errors->has('rental_rate'))
                                <span class="text-danger text-left">{{ $errors->first('rental_rate') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12 nopadding-left for-rental">
                            <label for="total_rental_rate" class="form-label"> Total Rental </label>
                            <input type="number" step="0.01" min="0" placeholder="Total Rental" id="total_rental_rate" name="total_rental_rate" class="form-control" value="{{ old('rental_rate', $rental->total_rental_rate) }}" readonly>
                        </div>

                        <div class="mb-3 col-12">
                            <label for="removal_rate" class="form-label"> Removal <span class="text-danger"> * </span> </label>
                            <input type="number" step="0.01" min="0" name="removal_rate" placeholder="Removal" id="removal_rate" class="form-control" value="{{ old('removal_rate', $rental->removal) }}">

                            @if ($errors->has('removal_rate'))
                                <span class="text-danger text-left">{{ $errors->first('removal_rate') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="pay_method" class="form-label"> Pay Method <span class="text-danger"> * </span> </label>
                            <select name="pay_method" id="pay_method">
                                <option value="fixed" @if($rental->pay_method == 'fixed') selected @endif >Fixed</option>
                                <option value="percentage" @if($rental->pay_method == 'percentage') selected @endif >Percentage</option>
                            </select>

                            @if ($errors->has('pay_method'))
                                <span class="text-danger text-left">{{ $errors->first('pay_method') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="driver" class="form-label"> Driver <span class="text-danger"> * </span> </label>
                            <input type="number" step="0.01" min="0" name="driver" placeholder="Driver" id="driver" class="form-control" value="{{ old('driver', $rental->driver) }}">

                            @if ($errors->has('driver'))
                                <span class="text-danger text-left">{{ $errors->first('driver') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="second" class="form-label"> Second <span class="text-danger"> * </span> </label>
                            <input type="number" step="0.01" min="0" name="second" placeholder="Second" id="second" class="form-control" value="{{ old('second', $rental->second) }}">

                            @if ($errors->has('second'))
                                <span class="text-danger text-left">{{ $errors->first('second') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="tax" class="form-label"> Tax <span class="text-danger"> * </span> </label>
                            <select name="tax" id="tax">
                                @if(isset($rental->thetax->id))
                                    <option value="{{ $rental->thetax->id }}" data-rate="{{ $rental->thetax->rate }}"> {{ $rental->thetax->description }} - {{ $rental->thetax->code }} </option>
                                @endif
                            </select>

                            @if ($errors->has('tax'))
                                <span class="text-danger text-left">{{ $errors->first('tax') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="tipping_fee_type" class="form-label"> Tipping Fee Method <span class="text-danger"> * </span> </label>
                            <select name="tipping_fee_type" id="tipping_fee_type">
                                <option value="fixed" @if($rental->tipping_fee_type == 'fixed') selected @endif > Fixed </option>
                                <option value="weight" @if($rental->tipping_fee_type == 'weight') selected @endif > Weight </option>
                            </select>

                            @if ($errors->has('tipping_fee_type'))
                                <span class="text-danger text-left">{{ $errors->first('tipping_fee_type') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="tipping_fee" class="form-label"> Tipping Fee <span class="text-danger"> * </span> </label>
                            <input type="number" step="0.01" min="0" name="tipping_fee" placeholder="Tipping Fee" id="tipping_fee" class="form-control" value="{{ old('tipping_fee', $rental->tipping_fee) }}">

                            @if ($errors->has('tipping_fee'))
                                <span class="text-danger text-left">{{ $errors->first('tipping_fee') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="status" class="form-label"> Status <span class="text-danger"> * </span> </label>
                            <select name="status" id="status" required>
                                <option value="0" @if($rental->status == 0) selected @endif > Pending </option>
                                <option value="1" @if($rental->status == 1) selected @endif > Cancelled </option>
                                <option value="2" @if($rental->status == 2) selected @endif > In Progress </option>
                                <option value="3" @if($rental->status == 3) selected @endif > Completed </option>
                            </select>

                            @if ($errors->has('status'))
                                <span class="text-danger text-left">{{ $errors->first('status') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12 nopadding-left days-input-visibility @if($rental->contract_type) d-none @endif">
                            <label for="days" class="form-label"> Days <span class="text-danger"> * </span> </label>
                            <input type="number" min="1" name="days" placeholder="Days" id="days" class="form-control" value="{{ $rental->total_rental_contract_days }}" readonly>

                            @if ($errors->has('days'))
                                <span class="text-danger text-left">{{ $errors->first('days') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="rental_counter" class="form-label"> Total <span class="label-of-service-removal"> @if($rental->contract_type) Services @else Removals @endif </span> <span class="text-danger"> * </span> </label>
                            <input type="number" min="1" class="form-control" id="rental_counter" name="rental_counter" value="{{ $rental->total_removals }}" readonly>

                            @if ($errors->has('rental_counter'))
                                <span class="text-danger text-left">{{ $errors->first('rental_counter') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <input type="checkbox" name="flat_rate_check" id="flat_rate_check" value="1" style="height: 20px;width:20px;" @if($rental->flat_rate_billing) checked @endif />
                            <label for="flat_rate_check" class="form-label" style="position: relative;bottom: 4px;left: 5px;"> Flat rate</label>
                        </div>

                        <div class="mb-3 col-12 nopadding-left">
                            <input type="checkbox" value="1" name="has_zero_total" id="has_zero_total" style="height: 20px;width: 20px;" @if($rental->has_zero_total) checked @endif>
                            <label for="has_zero_total" class="form-label" style="position: relative;bottom: 4px;left: 5px;"> Zero value rental </label>
                        </div>

                        <div class="mb-3 col-12">
                            <label for="total_rental" class="form-label"> Sub Total <span class="text-danger"> * </span> </label>
                            <input type="number" min="0" step="0.01" name="total_rental" placeholder="Total Rental" id="total_rental" class="form-control" value="{{ Helper::number_format($rental->sub_total) }}" @if(!$rental->flat_rate_billing || in_array($rental->status, [1, 3])) readonly @endif >
                            <input type="hidden" name="old_total_rental" id="old_total_rental" class="form-control" value="{{ Helper::number_format($rental->sub_total) }}">

                            @if ($errors->has('total_rental'))
                                <span class="text-danger text-left">{{ $errors->first('total_rental') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="total_tax" class="form-label"> Tax <span class="text-danger"> * </span> </label>
                            <input type="number" step="0.01" min="0" name="total_tax" placeholder="Total Tax" id="total_tax" class="form-control" value="{{ Helper::number_format($rental->sub_total_tax) }}" readonly>

                            @if ($errors->has('total_tax'))
                                <span class="text-danger text-left">{{ $errors->first('total_tax') }}</span>
                            @endif
                        </div>

                        <div class="mb-3 col-12">
                            <label for="total" class="form-label"> Grand Total <span class="text-danger"> * </span> </label>
                            <input type="number" step="0.01" min="0" name="total" placeholder="Total" id="total" @if($rental->has_zero_total) style="display:none;" @endif class="form-control" value="{{ Helper::number_format($rental->total) }}" readonly>
                            <div class="form-control" id="grand-zero-total">{{ number_format($rental->total, 2) }}</div>

                            @if ($errors->has('total'))
                                <span class="text-danger text-left">{{ $errors->first('total') }}</span>
                            @endif
                        </div>

                    </div>
                </div>
                </div>

                <button type="submit" id="mainFormSbmtBtn" class="btn btn-primary">Save</button>
                <a href="{{ route('rentals.index') }}" class="btn btn-default">Back</a>
            </form>
        </div>

    </div>

@endsection

@push('js')
    <script src="{{ asset('assets/js/jquery.datetimepicker.js') }}"></script>
    <script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/frequency.js') }}"></script>

    <script>
    var dailyRentalRate = parseFloat({{ $rental->daily_rental }}).toFixed(2);
    var weeklyRentalRate = parseFloat({{ $rental->weekly_rental }}).toFixed(2);
    var monthlyRentalRate = parseFloat({{ $rental->monthly_rental }}).toFixed(2);
    var newRentalRate = 0;
    var finalRemovalRate = 0;
    var theSevenDaysLaterActual = "{{ \Carbon\Carbon::now()->endOfMonth()->format('d-m-Y H:i') }}";
    var theSevenDaysLater = theSevenDaysLaterActual;
    var globalChargeBasis = "{{ $rental->charge_basis }}";

        $(document).ready(function() {

            $(document).on('change', '#isfreq', function () {
                if ($(this).is(':checked')) {
                    $('#container-for-perpetual').css('display', 'inline-block');
                    $('#container-for-new-rental').css('display', 'inline-block');
                    $('#container-for-first-removal').css('display', 'inline-block');
                    $('#container-for-frequency').css('display', 'flex');

                    if ($('#days').parent().hasClass('d-none')) {
                        $('#days').parent().removeClass('d-none');
                    }

                    if ($('#rental_counter').parent().hasClass('d-none')) {
                        $('#rental_counter').parent().removeClass('d-none');
                    }
                } else {
                    $('#container-for-perpetual').css('display', 'none');
                    $('#container-for-new-rental').css('display', 'none');
                    $('#container-for-first-removal').css('display', 'none');
                    $('#container-for-frequency').css('display', 'none');

                    if (!$('#show-only-days').hasClass('d-none')) {
                        $('#show-only-days').addClass('d-none');
                    }

                    $('#perpatual').prop('checked', false);
                    $('#check_days').prop('checked', false);

                    $('#monday').prop('checked', false);
                    $('#tuesday').prop('checked', false);
                    $('#wednesday').prop('checked', false);
                    $('#thursday').prop('checked', false);
                    $('#friday').prop('checked', false);
                    $('#saturday').prop('checked', false);
                    $('#sunday').prop('checked', false);

                    $('#deliver').val($('#from').val());
                    $('#removal').val($('#to').val());
                    $('#frequency').val(1).trigger('change');

                    if (!$('#days').parent().hasClass('d-none')) {
                        $('#days').parent().addClass('d-none');
                    }

                    if (!$('#rental_counter').parent().hasClass('d-none')) {
                        $('#rental_counter').parent().addClass('d-none');
                    }

                    if ($('.hidable-endate').hasClass('invisible')) {
                        $('.hidable-endate').removeClass('invisible');
                    }                    
                }
            });

            $('#perpatual').on('click', function () {
                if ($(this).is(':checked')) {
                    $('.hidable-endate').addClass('invisible');
                    $('.show-on-perp').removeClass('invisible');                    
                    $('#to').val(theSevenDaysLater);
                } else {
                    $('.hidable-endate').removeClass('invisible');
                    $('.show-on-perp').addClass('invisible');                    
                    $('#to').val('');
                }

                if ($('#contracttype option:selected').val() == 2) {
                    let tempFirstRemovalDate = $('#from').val();
                    theSevenDaysLater = moment(tempFirstRemovalDate, "DD-MM-YYYY HH:mm").endOf('month').format('DD-MM-YYYY HH:mm');
                } else {
                    let tempFirstRemovalDate = $('#removal').val();
                    theSevenDaysLater = moment(tempFirstRemovalDate, "DD-MM-YYYY HH:mm").endOf('month').format('DD-MM-YYYY HH:mm');                    
                }

                $('#to').val(theSevenDaysLater);

                // Fill Frequencies
                let tempFirstRemovalDate = $('#removal').val();
                let toDate = $('#to').val();

                let tempFreqRes = Frequency.acceptableFrequencies(tempFirstRemovalDate, toDate, $('#frequency_time').val());
                let oldFreqVal = $('#frequency').val();

                if (Object.keys(tempFreqRes).length > 0) {
                    $('#frequency').html('');

                    for (singleOpt in tempFreqRes) {
                        $('#frequency').append(`<option value="${singleOpt}"> ${tempFreqRes[singleOpt].name} </option>`);
                    }

                    if (!isNaN(oldFreqVal) && oldFreqVal >= 1 && oldFreqVal <= 8) {
                        $('#frequency').val(oldFreqVal).trigger('change');
                    }
                }
                // Fill Frequencies                

                calculateAmount();                
            });

            $('#has_zero_total').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#total').css({
                        'display' : 'none'
                    })
                    $('#grand-zero-total').css({
                        'display' : 'inline-block'
                    }).text($('#total').val())
                } else {
                    $('#total').css({
                        'display' : 'block'
                    })
                    $('#grand-zero-total').css({
                        'display' : 'none'
                    }).text($('#total').val())
                }
            });

            
            $('#contracttype').on('change', function () {
                if ($('option:selected', this).val() == 2) { //service
                    // Date Changes

                    let considerThisStartDate = moment($('#from').val(), "DD-MM-YYYY HH:mm").format('DD-MM-YYYY HH:mm');

                    $('#deliver').val(considerThisStartDate);
                    $('#removal').val(considerThisStartDate);

                    // Date Changes

                    if (!$('.days-input-visibility').hasClass('d-none')) {
                        $('.days-input-visibility').addClass('d-none');
                    }
                    $('.label-of-service-removal').text('Services');

                    if (!$('#container-for-rental-service-date').hasClass('d-none')) {
                        $('#container-for-rental-service-date').addClass('d-none');
                    }

                    $('#start-date-from-label').text('Service Start Date');
                    $('#end-date-to-label').text('Service End Date');

                } else {
                    $('.days-input-visibility').removeClass('d-none');
                    $('.label-of-service-removal').text('Removals');
                    $('#container-for-rental-service-date').removeClass('d-none');

                    $('#start-date-from-label').text('Start Date');
                    $('#end-date-to-label').text('End Date'); 
                }
            });

            function getPriceLevelRateData() {
                return $.ajax({
                    url: "{{ route('get-price-level-rate') }}",
                    type: 'POST',
                    data: {
                        _token : "{{ csrf_token() }}",
                        inventory_id : function () {
                            return $('#skip option:selected').val();
                        },
                        price_level_id : function () {
                            return $('#price_levels option:selected').val();
                        }
                    },
                    beforeSend: function () {
                        $('body').find('.LoaderSec').removeClass('d-none');
                    },
                    success: function (response) {
                        $('#price_level_rate_id').val(null);
                        if (response.status) {
                            if (response.data.type == 'price_level_rate') {
                                $('#price_level_rate_id').val(response.data.id);
                                $('#removal_rate').val(response.data.rate);
                            } else if (response.data.type == 'inventory') {
                                $('#removal_rate').val(response.data.rate);
                            } else {
                                $('#removal_rate').val(response.data.rate);
                            }
                        }
                    },
                    complete: function () {
                        $('body').find('.LoaderSec').addClass('d-none');
                    }
                });
            }

            function calculateAmount () {
                let isMultipleDays = $('#check_days').is(':checked');
                let selectedFrequency = $('#frequency option:selected').val();
                let isFullDateMonth = false;

                let thisTempDays = 0;
                let tempFromDate = $('#deliver').val();
                let tempFirstRemovalDate = $('#removal').val();
                let tempToDate = $('#to').val();

                if (typeof tempFromDate === 'string' && typeof tempToDate === 'string') {
                    let fromParts = tempFromDate.split(/[- :]/);
                    let toParts = tempToDate.split(/[- :]/);

                    let startDate = new Date(fromParts[2], fromParts[1] - 1, fromParts[0], fromParts[3], fromParts[4]);
                    let endDate = new Date(toParts[2], toParts[1] - 1, toParts[0], toParts[3], toParts[4]);

                    let differenceInMs = endDate - startDate;

                    let differenceInDays = differenceInMs / (1000 * 60 * 60 * 24);

                    differenceInDays = Math.floor(differenceInDays);
                 
                    thisTempDays = differenceInDays >= 0 ? differenceInDays + 1 : differenceInDays;
                }

                if (Frequency.isFullMonth(tempFromDate, tempToDate)) {
                    isFullDateMonth = true;
                }

                thisTempDays -= 1;

                let getTheCOuntOfDays = getTotalWeeksCount(thisTempDays);

                $('#cbc').val(thisTempDays);
                $('#cbcd').val(`${getTheCOuntOfDays} ${thisTempDays > 2 ? 'week' : 'day'}`);

                $('#cb').val(globalChargeBasis);

                let rentalMultipler = 0;

                if (isMultipleDays) {
                    if ($('.show-only-days input[type="checkbox"]').toArray().some((item) => $(item).is(':checked'))) {
                        let selectedWeekDays = [];

                        $('.show-only-days input[type="checkbox"]').each(function (index, value) {
                            if ($(this).is(':checked')) {
                                selectedWeekDays.push($(this).attr('data-week'));
                            }
                        });

                        if (selectedWeekDays.length > 0) {
                            selectedWeekDays.forEach(thisEl => {
                                rentalMultipler += Frequency.countWeekSpecificDays(tempFirstRemovalDate, tempToDate, thisEl); 
                            });
                        }

                        if (rentalMultipler > 0) {
                            $('#rental_counter').val(rentalMultipler);
                        } else {
                            $('#rental_counter').val(0);
                        }
                        
                    }

                } else {
                    if (selectedFrequency >= 1 && selectedFrequency <= 8) {
                        let thisResult = Frequency.getRemovals(tempFirstRemovalDate, tempToDate, $('#frequency_time').val(), selectedFrequency);
                                                
                        if (thisResult && 'count' in thisResult) {
                            rentalMultipler = thisResult.count;
                            $('#rental_counter').val(rentalMultipler);
                        } else {
                            $('#rental_counter').val(0);
                        }
                    }

                }


                let chargeBasisCount = parseInt($('#cbc').val());

                if (globalChargeBasis == 'daily') {
                    let fixedRentalRate = dailyRentalRate;

                    $('#rental_rate').val(parseFloat(fixedRentalRate).toFixed(2));
                    $('#total_rental_rate').val(parseFloat(fixedRentalRate * chargeBasisCount).toFixed(2));
                } else {
                    let fixedRentalRate = chargeBasisCount > 2 ? monthlyRentalRate : dailyRentalRate;
                    fixedRentalRate = getTotalWeeksRate(chargeBasisCount, fixedRentalRate);
                    

                    $('#rental_rate').val(parseFloat(fixedRentalRate).toFixed(2));
                    $('#total_rental_rate').val(parseFloat(fixedRentalRate * getTotalWeeksCount(chargeBasisCount)).toFixed(2));
                }

                // Calculations

                let totalRental = parseFloat($('#total_rental').val());
                let totalRemoval = parseFloat($('#removal_rate').val());

                let rentalRate = parseFloat($('#rental_rate').val());
                let totalDays = parseInt($('#rental_counter').val());
                let selectedTax = parseFloat($('option:selected', '#tax').attr('data-rate'));

                if (totalDays > 0) {
                    let thisNewRentalVar = newRentalRate;
                    let thisFinalRemovalVar = finalRemovalRate;

                    if ($('#rental_counter').val() <= 1) {
                        thisFinalRemovalVar = 0;
                    }

                    if ($('#customer option:selected').attr('data-flat_billing_rate') != '1') {
                        if (isNaN(totalRemoval)) {
                            totalRemoval = 1;
                        }

                        let newRentalTotal = 0;

                        if (globalChargeBasis == 'daily') {
                            newRentalTotal = parseFloat((rentalRate * chargeBasisCount) + parseFloat(thisNewRentalVar) + (totalRemoval * rentalMultipler) + parseFloat(thisFinalRemovalVar));
                        } else {
                            newRentalTotal = parseFloat((rentalRate * getTotalWeeksCount(chargeBasisCount)) + parseFloat(thisNewRentalVar) + (totalRemoval * rentalMultipler) + parseFloat(thisFinalRemovalVar));
                        }
                        
                        let newGrandTotal = newRentalTotal;
                        let newTax = 0;

                        if (selectedTax >= 0) {
                            newTax = parseFloat((newGrandTotal * selectedTax) / 100);
                            newGrandTotal += newTax;
                        }

                        $('#total_rental').val(newRentalTotal.toFixed(2));
                        $('#old_total_rental').val(newRentalTotal.toFixed(2));                        
                        $('#total_tax').val(newTax.toFixed(2));
                        $('#total').val(parseFloat(newGrandTotal).toFixed(2));
                        $('#grand-zero-total').text(parseFloat(newGrandTotal).toFixed(2));
                    }
                } else {
                    $('#total_rental').val(parseFloat(0).toFixed(2));
                    $('#old_total_rental').val(parseFloat(0).toFixed(2));
                    $('#total_tax').val(parseFloat(0).toFixed(2));
                    $('#total').val(parseFloat(0).toFixed(2));
                    $('#grand-zero-total').text(parseFloat(0).toFixed(2));
                }
            }
            function getTotalWeeksCount(thisDaysCount) {
                if (isNaN(thisDaysCount)) {
                    return 0;
                }

                if (thisDaysCount == 0) {
                    thisDaysCount = 1;
                }

                if (thisDaysCount > 2) {
                    let itRound = Math.round(thisDaysCount / 7);
                    return itRound <= 0 ? 1 : itRound;
                } else {
                    return thisDaysCount;
                }
            }

            function getTotalWeeksRate(thisDaysCount, rate) {                
                if (isNaN(thisDaysCount)) {
                    return 0;
                }

                if (thisDaysCount > 2) {
                    return rate / 4;
                } else {
                    return rate;
                }
            }

            function initializeFrequencySelect2() {
                $('#frequency').select2({
                    allowClear: true,
                    placeholder: 'Select a frequency',
                    width: '100%',
                    theme: 'classic'
                }).on('change', function () {
                    calculateAmount();
                });
            }

            function initializeCustomerSelect2() {
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
                                        text: item.text,
                                        businesstype: item.businesstype,
                                        paymentterms: item.paymentterms,
                                        pricelevel: item.pricelevel,
                                        tax: item.tax,
                                        charge_basis: item.charge_basis,
                                        flat_billing_rate: item.flat_billing_rate,
                                        flat_rate: item.flat_rate,
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

                        if ('businesstype' in data && typeof data.businesstype != 'undefined' && data.businesstype != null) {
                            $result.attr('data-businesstype', JSON.stringify(data.businesstype));
                        }

                        if ('paymentterms' in data && typeof data.paymentterms != 'undefined' && data.paymentterms != null) {
                            $result.attr('data-paymentterms', JSON.stringify(data.paymentterms));
                        }

                        if ('pricelevel' in data && typeof data.pricelevel != 'undefined' && data.pricelevel != null) {
                            $result.attr('data-pricelevel', JSON.stringify(data.pricelevel));
                        }

                        if ('tax' in data && typeof data.tax != 'undefined' && data.tax != null) {
                            $result.attr('data-tax', JSON.stringify(data.tax));
                        }
                        
                        $result.attr('data-flat_billing_rate', data.flat_billing_rate);
                        $result.attr('data-charge_basis', data.charge_basis);
                        $result.attr('data-flat_rate', data.flat_rate);
                        $result.text(data.text);

                        return $result;
                    },
                    templateSelection: function (container) {

                        $(container.element).attr("data-businesstype", JSON.stringify(container.businesstype));
                        $(container.element).attr("data-paymentterms", JSON.stringify(container.paymentterms));
                        $(container.element).attr("data-pricelevel", JSON.stringify(container.pricelevel));
                        $(container.element).attr("data-tax", JSON.stringify(container.tax));
                        $(container.element).attr("data-charge_basis", container.charge_basis);
                        $(container.element).attr("data-flat_rate", container.flat_rate);
                        $(container.element).attr("data-flat_billing_rate", container.flat_billing_rate);
                                                    
                        return container.text;
                    }
                }).on('change', function (e) {
                    if ($('option:selected', this).val() == null || $('option:selected', this).val() == '') {
                        $('#location').val(null).trigger('change');
                        $('#pt').val(null).trigger('change');
                        
                        $('#total').val(parseFloat(0).toFixed(2));
                        $('#grand-zero-total').text(parseFloat(0).toFixed(2));
                        $('#total_tax').val(parseFloat(0).toFixed(2));
                        $('#total_rental').val(parseFloat(0).toFixed(2));
                        $('#old_total_rental').val(parseFloat(0).toFixed(2));                        
                        $('#flat_rate_check').prop('checked', false);

                    } else {

                        if ($('option:selected', this).attr('data-charge_basis') == 'daily' || $('option:selected', this).attr('data-charge_basis') == 'monthly') {
                            globalChargeBasis = $('option:selected', this).attr('data-charge_basis');
                        }                        

                        var paymentTerm = $('option:selected', this).attr('data-paymentterms');

                        if (typeof paymentTerm == 'string') {
                            paymentTerm = JSON.parse(paymentTerm);

                            if (typeof paymentTerm == 'object' && 'id' in paymentTerm) {
                                let newOption = new Option(`${paymentTerm.code} - ${paymentTerm.name}`, paymentTerm.id, true, true);
                                $('#pt').append(newOption).trigger('change');
                            }
                        }

                        var taxJson = $('option:selected', this).attr('data-tax');

                        if (typeof taxJson == 'string') {
                            taxJson = JSON.parse(taxJson);

                            if (typeof taxJson == 'object' && 'id' in taxJson) {
                                let newOption = new Option(`${taxJson.code} - ${taxJson.description}`, taxJson.id, true, true);
                                $(newOption).attr('data-rate', taxJson.rate);
                                $('#tax').append(newOption).trigger('change');
                            }
                        }

                        var priceLevel = $('option:selected', this).attr('data-pricelevel');

                        if (typeof priceLevel == 'string') {
                            priceLevel = JSON.parse(priceLevel);

                            if (typeof priceLevel == 'object' && 'id' in priceLevel) {
                                let newOption = new Option(`${priceLevel.code} - ${priceLevel.title}`, priceLevel.id, true, true);

                                if (!isNaN(priceLevel.removal_rate) && priceLevel.removal_rate >= 0) {
                                    $(newOption).attr('data-removal_rate', priceLevel.removal_rate);
                                    $('#price_levels').append(newOption).trigger('change');
                                }
                            }
                        }

                        if ($('option:selected', this).attr('data-flat_billing_rate') == 1) {
                            $('#flat_rate_check').prop('checked', true);

                            $('#total_rental').val(parseFloat($('option:selected', this).attr('data-flat_rate')).toFixed(2)).prop('readonly', false);
                            $('#old_total_rental').val(parseFloat($('option:selected', this).attr('data-flat_rate')).toFixed(2));
                            $('#total_tax').val(0.00).prop('readonly', true);
                            $('#total').val(parseFloat($('option:selected', this).attr('data-flat_rate')).toFixed(2)).prop('readonly', true);
                            $('#grand-zero-total').text(parseFloat($('option:selected', this).attr('data-flat_rate')).toFixed(2));
                        } else {
                            $('#flat_rate_check').prop('checked', false);

                            $('#total_rental').val(parseFloat($('option:selected', this).attr('data-flat_rate')).toFixed(2)).prop('readonly', true);
                            $('#old_total_rental').val(parseFloat($('option:selected', this).attr('data-flat_rate')).toFixed(2));
                            $('#total_tax').val(0.00).prop('readonly', true);
                            $('#total').val(parseFloat($('option:selected', this).attr('data-flat_rate')).toFixed(2)).prop('readonly', true);
                            $('#grand-zero-total').text(parseFloat($('option:selected', this).attr('data-flat_rate')).toFixed(2));
                        }
                        
                    }
                });
            }

            function initializeLocationSelect2() {
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
                                        address: item.address
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
                        $result.text(data.text);
                        return $result;
                    },
                    templateSelection: function (container) {
                        if ('address' in container && container.address != null) {
                            $(container.element).attr("data-address", container.address);
                        }

                        return container.text;
                    }
                }).on('change', function (e) {
                    $('#fetch-location').val($('option:selected', this).attr('data-address'));
                });
            }

            function initializeRequestMethodSelect2() {
                $('#request_method').select2({
                    allowClear: true,
                    placeholder: 'Select a request method',
                    width: '100%',
                    theme: 'classic',
                    ajax: {
                        url: "{{ route('get-request-method-json') }}",
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

            function initializeSkipSelect2() {
                $('#skip').select2({
                    allowClear: true,
                    placeholder: 'Select a skip',
                    width: '100%',
                    theme: 'classic',
                    ajax: {
                        url: "{{ route('get-inventory-json') }}",
                        type: "POST",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                searchQuery: params.term,
                                page: params.page || 1,  
                                _token: "{{ csrf_token() }}",
                                type: function () {
                                    return $('#contracttype option:selected').val() == 1 ? 0 : 1;
                                },
                                all: 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;

                            return {
                                results: $.map(data.items, function(item) {
                                    return {
                                        id: item.id,
                                        text: item.text,
                                        monthly_rental: item.monthly_rental,
                                        daily_rental: item.daily_rental,
                                        weekly_rental: item.weekly_rental,
                                        removal: item.removal,
                                        quantity: item.quantity,
                                        reserved: item.reserved,
                                        pay_method: item.pay_method,
                                        driver_pay: item.driver_pay,
                                        second_pay: item.second_pay,
                                        new_rental: item.new_rental,
                                        final_removal: item.final_removal,
                                        tipping_fee_unit: item.tipping_fee_unit,
                                        tipping_fee: item.tipping_fee,
                                        price_levels_id: item.price_levels_id,
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

                        $result.attr('data-monthly_rental', data.monthly_rental);
                        $result.attr('data-daily_rental', data.daily_rental);
                        $result.attr('data-weekly_rental', data.weekly_rental);
                        $result.attr('data-removal', data.removal);
                        $result.attr('data-quantity', data.quantity);
                        $result.attr('data-reserved', data.reserved);
                        $result.attr('data-pay_method', data.pay_method);
                        $result.attr('data-driver_pay', data.driver_pay);
                        $result.attr('data-second_pay', data.second_pay);
                        $result.attr('data-new_rental', data.new_rental);
                        $result.attr('data-final_removal', data.final_removal);
                        $result.attr('data-tipping_fee_unit', data.tipping_fee_unit);
                        $result.attr('data-tipping_fee', data.tipping_fee);
                        $result.attr('data-price_levels_id', data.price_levels_id);

                        $result.text(data.text);
                        return $result;
                    },
                    templateSelection: function (container) {

                        $(container.element).attr("data-monthly_rental", container.monthly_rental);
                        $(container.element).attr("data-daily_rental", container.daily_rental);
                        $(container.element).attr("data-weekly_rental", container.weekly_rental);
                        $(container.element).attr("data-removal", container.removal);
                        $(container.element).attr("data-quantity", container.quantity);
                        $(container.element).attr("data-reserved", container.reserved);
                        $(container.element).attr("data-pay_method", container.pay_method);
                        $(container.element).attr("data-driver_pay", container.driver_pay);
                        $(container.element).attr("data-second_pay", container.second_pay);
                        $(container.element).attr("data-new_rental", container.new_rental);
                        $(container.element).attr("data-final_removal", container.final_removal);
                        $(container.element).attr("data-tipping_fee_unit", container.tipping_fee_unit);
                        $(container.element).attr("data-tipping_fee", container.tipping_fee);
                        $(container.element).attr("data-price_levels_id", container.price_levels_id);

                        return container.text;
                    }
                }).on('change', function () {

                    let AllData = $('option:selected', this).data();

                        if (typeof AllData == 'object') {
                            if ('pay_method' in AllData) {
                                $('#pay_method').val(AllData.pay_method).trigger('change');
                            }

                            if ('driver_pay' in AllData) {
                                $('#driver').val(parseFloat(AllData.driver_pay).toFixed(2));
                            }

                            if ('second_pay' in AllData) {
                                $('#second').val(parseFloat(AllData.second_pay).toFixed(2));
                            }

                            if ('tipping_fee' in AllData) {
                                $('#tipping_fee').val(parseFloat(AllData.tipping_fee).toFixed(2));
                            }

                            if ('tipping_fee_unit' in AllData) {
                                $('#tipping_fee_type').val(AllData.tipping_fee_unit).trigger('change');
                            }

                            dailyRentalRate = parseFloat(AllData.daily_rental).toFixed(2);
                            weeklyRentalRate = parseFloat(AllData.weekly_rental).toFixed(2);
                            monthlyRentalRate = parseFloat(AllData.monthly_rental).toFixed(2);
                            finalRemovalRate = 0;
                            newRentalRate = 0;
                        } else {
                            dailyRentalRate = 0;
                            weeklyRentalRate = 0;
                            monthlyRentalRate = 0;
                            finalRemovalRate = 0;
                            newRentalRate = 0;
                        }

                    getPriceLevelRateData().then(function() {
                        calculateAmount();
                    })
                });
            }

            function initializeWasteTypeSelect2() {
                $('#waste_type').select2({
                    allowClear: true,
                    placeholder: 'Select a waste type',
                    width: '100%',
                    theme: 'classic',
                    ajax: {
                        url: "{{ route('get-waste-type-json') }}",
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

            function initializePriceLevelSelect2() {
                $('#price_levels').select2({
                    allowClear: true,
                    placeholder: 'Select a price level',
                    width: '100%',
                    theme: 'classic',
                    ajax: {
                        url: "{{ route('get-price-level-json') }}",
                        type: "POST",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                searchQuery: params.term,
                                page: params.page || 1,  
                                _token: "{{ csrf_token() }}",
                                selected : function () {
                                    return $('#skip option:selected').attr('data-price_levels_id');
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
                                        removal_rate: item.removal_rate
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
                        $result.text('data-removal_rate', data.removal_rate);
                        $result.text(data.text);
                        return $result;
                    },
                    templateSelection: function (container) {
                        if ('removal_rate' in container && !isNaN(container.removal_rate) && container.removal_rate >= 0) {
                            $(container.element).attr("data-removal_rate", parseFloat(container.removal_rate).toFixed(2));
                        }

                        return container.text;
                    }
                }).on('change', function () {
                    getPriceLevelRateData().then(function() {
                        calculateAmount();
                    })
                });
            }

            function initializeCompanySelect2() {
                $('#company').select2({
                    allowClear: true,
                    placeholder: 'Select a company',
                    width: '100%',
                    theme: 'classic',
                    ajax: {
                        url: "{{ route('get-company-json') }}",
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

            function initializeRequestedBySelect2() {
                $('#request_by').select2({
                    allowClear: true,
                    placeholder: 'Select a contact',
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
                                role: "{{ Helper::$roles['customer'] }}"
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

            function initializeTaxSelect2() {
                $('#tax').select2({
                    allowClear: true,
                    placeholder: 'Select a tax',
                    width: '100%',
                    theme: 'classic',
                    ajax: {
                        url: "{{ route('get-rental-tax-json') }}",
                        type: "POST",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                searchQuery: params.term,
                                page: params.page || 1,  
                                _token: "{{ csrf_token() }}",
                                all : 1,
                                customer_id : function () {
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
                                        rate: item.rate
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
                        $result.attr('data-rate', data.rate);
                        $result.text(data.text);
                        return $result;
                    },
                    templateSelection: function (container) {

                        if ('rate' in container && !isNaN(container.rate) && container.rate >= 0) {
                            $(container.element).attr("data-rate", container.rate);
                        }
                                                    
                        return container.text;
                    }
                }).on('change', function () {
                    let thisRate = $(this).select2('data')[0];

                    if (!isNaN(thisRate.rate) && thisRate.rate >= 0) {
                        let totalRental = parseFloat($('#total_rental').val());
                        let selectedTax = thisRate.rate;

                        if (!isNaN(totalRental)) {
                            let onlyTaxAmount = parseFloat((totalRental * selectedTax) / 100);


                            $('#total_rental').val(parseFloat(totalRental).toFixed(2));
                            $('#old_total_rental').val(parseFloat(totalRental).toFixed(2));                            
                            $('#total_tax').val(parseFloat(onlyTaxAmount).toFixed(2));
                            $('#total').val(parseFloat(totalRental + onlyTaxAmount).toFixed(2));
                            $('#grand-zero-total').text(parseFloat(totalRental + onlyTaxAmount).toFixed(2));
                        } else {
                            $('#total_rental').val(parseFloat(0).toFixed(2));
                            $('#old_total_rental').val(parseFloat(0).toFixed(2));
                            $('#total_tax').val(parseFloat(0).toFixed(2));
                            $('#total').val(parseFloat(0).toFixed(2));
                            $('#grand-zero-total').text(parseFloat(0).toFixed(2));
                        }
                    }
                });

            }

            function initializePayMethodFeeType() {
                $('#pay_method').select2({
                    allowClear: true,
                    placeholder: 'Select a pay method',
                    width: '100%',
                    theme: 'classic',
                });
            }

            function initializeTippingFeeType() {
                $('#tipping_fee_type').select2({
                    allowClear: true,
                    placeholder: 'Select a pay method',
                    width: '100%',
                    theme: 'classic',
                });
            }

            function initializeStatusSelect2() {
                $('#status').select2({
                    allowClear: true,
                    placeholder: 'Select a status',
                    width: '100%',
                    theme: 'classic',
                });
            }

            function initializeContractTypeSelect2() {
                $('#contracttype').select2({
                    allowClear: true,
                    placeholder: 'Select a contract type',
                    width: '100%',
                    theme: 'classic',
                }).on('change', function () {
                        $('.for-rental').removeClass('d-none');
                        $('.for-service').addClass('d-none');
                        $('#rental_rate').attr('placeholder', 'Rental Rate');
                        $("label[for=rental_rate]").text('Rental Rate');

                        dailyRentalRate = 0;
                        monthlyRentalRate = 0;
                        newRentalRate = 0;
                        finalRemovalRate = 0;

                        $('#skip').val(null).trigger('change');
                        calculateAmount();
                });
            }

            function initializePaymentTermSelect2() {
                $('#pt').select2({
                    allowClear: true,
                    placeholder: 'Select a payment term',
                    width: '100%',
                    theme: 'classic',
                    ajax: {
                        url: "{{ route('get-payment-terms-json') }}",
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

            function initializeVehicleSelect2() {
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
                                        driver_id: item.driver_id,
                                        second_man_id: item.second_man_id,
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

                        if ('driver_id' in data && typeof data.driver_id != 'undefined' && data.driver_id != null) {
                            $result.attr('data-driver_id', JSON.stringify(data.driver_id));
                        }

                        if ('second_man_id' in data && typeof data.second_man_id != 'undefined' && data.second_man_id != null) {
                            $result.attr('data-second_man_id', JSON.stringify(data.second_man_id));
                        }

                        $result.text(data.text);
                        return $result;
                    },
                    templateSelection: function (container) {

                        $(container.element).attr("data-driver_id", JSON.stringify(container.driver_id));
                        $(container.element).attr("data-second_man_id", JSON.stringify(container.second_man_id));
                                                    
                        return container.text;
                    }
                }).on('change', function () {

                    let driverObj = $('option:selected', this).attr('data-driver_id');

                    if (typeof driverObj == 'string') {
                        driverObj = JSON.parse(driverObj);
                    } else {
                        driverObj = '';
                    }

                    if (typeof driverObj == 'object' && 'id' in driverObj) {
                        let newOption = new Option(`${driverObj.code} - ${driverObj.name}`, driverObj.id, true, true);
                        $('#driver_id').append(newOption).trigger('change');
                    }

                    let secondObj = $('option:selected', this).attr('data-second_man_id');

                    if (typeof secondObj == 'string') {
                        secondObj = JSON.parse(secondObj);
                    } else {
                        secondObj = '';
                    }

                    if (typeof secondObj == 'object' && 'id' in secondObj) {
                        let newOption = new Option(`${secondObj.code} - ${secondObj.name}`, secondObj.id, true, true);
                        $('#second_man_id').append(newOption).trigger('change');
                    }

                });
            }

            function initializeServiceTypeSelect2() {
                $('#servicetype').select2({
                    allowClear: true,
                    placeholder: 'Select a service type',
                    width: '100%',
                    theme: 'classic',
                    ajax: {
                        url: "{{ route('get-service-type-json') }}",
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
                                        account_id: item.account_id,
                                        vehicle_id: item.vehicle_id,
                                        amount: item.amount,
                                        pay_method: item.pay_method,
                                        driver: item.driver,
                                        second: item.second 
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

                        $result.attr('data-account_id', data.account_id);
                        $result.attr('data-vehicle_id', data.vehicle_id);
                        $result.attr('data-amount', data.amount);
                        $result.attr('data-pay_method', data.pay_method);
                        $result.attr('data-driver', data.driver);
                        $result.attr('data-second', data.second);

                        $result.text(data.text);
                        return $result;
                    },
                    templateSelection: function (container) {

                        $(container.element).attr("data-account_id", container.account_id);
                        $(container.element).attr("data-vehicle_id", container.vehicle_id);
                        $(container.element).attr("data-amount", container.amount);
                        $(container.element).attr("data-pay_method", container.pay_method);
                        $(container.element).attr("data-driver", container.driver);
                        $(container.element).attr("data-second", container.second);
                                                    
                        return container.text;
                    }
                }).on('change', function () {
                    let thisData = $(this).select2('data')[0]
                        
                    if (typeof thisData != 'undefined') {
                        if ('vehicle_id' in thisData && !isNaN(thisData.vehicle_id)) {
                            $('#vehicle').val(thisData.vehicle_id).trigger('change');
                        }

                        if ('amount' in thisData && !isNaN(thisData.amount)) {
                            $('#rental_rate').val(parseFloat(thisData.amount).toFixed(2));
                        }

                        if ('driver' in thisData && !isNaN(thisData.driver)) {                    
                            $('#driver').val(parseFloat(thisData.driver).toFixed(2));
                        }

                        if ('second' in thisData && !isNaN(thisData.second)) {                    
                            $('#second').val(parseFloat(thisData.second).toFixed(2));
                        }
                    }

                });
            }

            function initializeDriverSelect2() {
                $('#driver_id').select2({
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
                    },
                    templateSelection: function (container) {                                                    
                        return container.text;
                    }
                });
            }

            function initializeSecondManSelect2() {
                $('#second_man_id').select2({
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
                                role: "{{ Helper::$roles['driver'] }},{{ Helper::$roles['second-man'] }}"
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
                    templateSelection: function (container) {                                                    
                        return container.text;
                    }
                });
            }

            $.validator.addMethod("dateFormat", function(value, element) {
                var regex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4}) (0[0-9]|1[0-9]|2[0-3]):([0-5][0-9])$/;
                return this.optional(element) || regex.test(value);
            }, "Please enter a date in the format dd-mm-yyyy hh:mm");

            $.validator.addMethod("greaterThan", function(value, element) {
                var startDate = $('#from').val();
                var start = moment(startDate, "DD-MM-YYYY HH:mm");
                var end = moment(value, "DD-MM-YYYY HH:mm");
                return this.optional(element) || end.isAfter(start);
            }, "Rental end date must be greater than start date");

            $.validator.addMethod("deliverDate", function(value, element) {
                const fromDate = moment($('#from').val(), "DD-MM-YYYY HH:mm");
                const toDate = moment($('#to').val(), "DD-MM-YYYY HH:mm");
                
                const parts = value.split(' ');
                const dateParts = parts[0].split('-');
                const timeParts = parts[1].split(':');
                const inputDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0], timeParts[0], timeParts[1]);

                return inputDate >= fromDate && inputDate <= toDate;
            }, "New rental date should be between rental start date and end date.");

            $.validator.addMethod("removalDate", function(value, element) {
                const fromDate = moment($('#deliver').val(), "DD-MM-YYYY HH:mm");
                const toDate = moment($('#to').val(), "DD-MM-YYYY HH:mm");
                
                const parts = value.split(' ');
                const dateParts = parts[0].split('-');
                const timeParts = parts[1].split(':');
                const inputDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0], timeParts[0], timeParts[1]);

                return inputDate >= fromDate && inputDate <= toDate;
            }, "First removal date should be between deliver date and end date.");

            $('#mainForm').validate({
                rules: {
                    code: {
                        required: true,
                        maxlength: 8
                    },
                    date: {
                        required: true,
                        dateFormat: true
                    },
                    customer: {
                        required: true
                    },
                    location: {
                        required: true
                    },
                    skip: {
                        required: true
                    },
                    servicetype: {
                        required: true
                    },
                    vehicle: {
                        required: true
                    },
                    request_method: {
                        required: true
                    },
                    waste_type: {
                        required: true
                    },
                    company: {
                        required: true
                    },
                    from: {
                        required: true,
                        dateFormat: true
                    },
                    to: {
                        required: (e) => {
                            if ($('#perpatual').is(':checked')) {
                                return false;
                            }

                            return true;
                        },
                        dateFormat: true,
                        greaterThan: "#from"
                    },
                    deliver: {
                        required: true,
                        dateFormat: true,
                        deliverDate: true
                    },
                    removal: {
                        required: true,
                        dateFormat: true,
                        removalDate: true
                    },
                    rental_rate: {
                        required: true,
                        min: 0
                    },
                    removal_rate: {
                        required: true,
                        min: 0
                    },
                    pay_method: {
                        required: true
                    },
                    driver: {
                        required: true,
                        min: 0
                    },
                    second: {
                        required: true,
                        min: 0
                    },
                    tax: {
                        required: true                
                    },
                    tipping_fee_type: {
                        required: true                
                    },
                    tipping_fee: {
                        required: true,
                        min: 0
                    },
                    status: {
                        required: true
                    },
                    frequency_time: {
                        required: true
                    },
                    pt: {
                        required: true
                    },

                },
                messages: {
                    code: {
                        required: "Code is required",
                        maxlength: 'Maximum code length should be 8 characters'
                    },
                    date: {
                        required: "Select a date and time"
                    },
                    customer: {
                        required: "Select a customer"
                    },
                    location: {
                        required: "Select a location"
                    },
                    skip: {
                        required: "Select a skip"
                    },
                    servicetype: {
                        required: "Select a service type"
                    },
                    vehicle: {
                        required: "Select a vehicle"
                    },
                    request_method: {
                        required: "Select a requested method"
                    },
                    waste_type: {
                        required: "Select a waste type"
                    },
                    company: {
                        required: "Select a company type"
                    },
                    from: {
                        required: "Select a rental start date"
                    },
                    to: {
                        required: "Select a rental end date"
                    },
                    deliver: {
                        required: "Select a new rental date"
                    },
                    removal: {
                        required: "Select a first removal date"
                    },
                    rental_rate: {
                        required: 'Enter rental rate',
                        min: 'Minimum rental rate should be 0'
                    },
                    removal_rate: {
                        required: 'Enter removal rate',
                        min: 'Minimum removal rate should be 0'
                    },
                    pay_method: {
                        required: 'Select pay method',
                    },
                    driver: {
                        required: "Enter driver's pay",
                        min: "Minimum driver's pay should be 0"
                    },
                    second: {
                        required: "Enter second man's amount",
                        min: "Minimum second man's pay should be 0"
                    },
                    tax: {
                        required: "Select a tax"
                    },
                    tipping_fee_type: {
                        required: "Select a tipping fee type"
                    },
                    tipping_fee: {
                        required: "Enter tipping fee",
                        min: "Minimum tipping fee should be 0"
                    },
                    status: {
                        required: "Select a status"
                    },
                    frequency_time: {
                        required: "Enter time"
                    },
                    pt: {
                        required: "Select a payment term"
                    },

                },
                errorPlacement: function(error, element) {
                    error.appendTo(element.parent("div"));
                }
            });

            $(document).on('submit', '#mainForm', function (e) {
                e.preventDefault();

                let isPeriodFrequency = $('#check_days').is(':checked');
                let proceed = false;
                let form = $(this);

                if (isPeriodFrequency) {
                        if ($('.show-only-days input[type="checkbox"]').toArray().some((item) => $(item).is(':checked'))) {
                            proceed = true;                            
                        } else {
                            Swal.fire('error', 'Please check atleast a day', 'error');
                        }
                } else {
                    if ($('#frequency option:selected').val() > 0) {
                        proceed = true;
                    } else {
                        Swal.fire('error', 'Please select a frequency', 'error');
                    }
                }

                if (proceed) {
                    $.ajax({
                        url: "{{ route('rental-get-dates') }}",
                        type: 'POST',
                        data: {
                            _token : "{{ csrf_token() }}",
                        },
                        beforeSend: function () {
                            $('body').find('.LoaderSec').removeClass('d-none');
                        },
                        success: function (response) {
                            if (response.status) {
                                e.currentTarget.submit();
                            }
                        },
                        complete: function () {
                            $('body').find('.LoaderSec').addClass('d-none');
                        }
                    });
                }
            });

            $('.show-only-days input[type="checkbox"]').on('change', function () {
                calculateAmount();
            });

            $('#removal_rate').on('change', function () {
                calculateAmount();
            });

            $('#total_tax').on('change', function () {
                let thisTax = parseFloat($(this).val());
                let totalRental = parseFloat($('#total_rental').val());

                $('#total').val(parseFloat( totalRental + thisTax ).toFixed(2));
                $('#grand-zero-total').text(parseFloat( totalRental + thisTax ).toFixed(2));
            });

            $('#total_rental').on('change', function () {
                let thisRental = parseFloat($(this).val());
                let totalTax = parseFloat($('#total_tax').val());
                let selectedTax = parseFloat($('option:selected', '#tax').attr('data-rate'));
                let newGrandTotal = thisRental;
                let newTax = 0;

                if (selectedTax >= 0) {
                    newTax = parseFloat((thisRental * selectedTax) / 100);
                    newGrandTotal += newTax;
                }

                $('#total_rental').val(thisRental.toFixed(2));
                $('#total_tax').val(newTax.toFixed(2));
                $('#total').val(parseFloat(newGrandTotal).toFixed(2));

                if ($('#customer option:selected').attr('data-flat_billing_rate') != '1') {
                    $('#grand-zero-total').text(parseFloat( thisRental + newTax ).toFixed(2));
                }
            });

            $('#rental_rate').on('change', function () {
                let thisSingleRental = parseFloat($(this).val());
                let toBeCalculatedWith = parseFloat($('#rental_counter').val());
                let newAmount = parseFloat(thisSingleRental * toBeCalculatedWith).toFixed(2);
                let toBeMultiplied = parseInt($('#cbc').val());
                toBeMultiplied = toBeMultiplied > 0 ? toBeMultiplied : 1;
                
                if ($('#customer option:selected').attr('data-flat_billing_rate') != '1') {

                    if (globalChargeBasis == 'daily') {
                        let fixedRentalRate = dailyRentalRate;

                        $('#rental_rate').val(parseFloat(fixedRentalRate).toFixed(2));
                        $('#total_rental_rate').val(parseFloat(fixedRentalRate * toBeMultiplied).toFixed(2));
                    } else {
                        let fixedRentalRate = toBeMultiplied > 2 ? monthlyRentalRate : dailyRentalRate;
                        fixedRentalRate = getTotalWeeksRate(toBeMultiplied, fixedRentalRate);
                        

                        $('#rental_rate').val(parseFloat(fixedRentalRate).toFixed(2));
                        $('#total_rental_rate').val(parseFloat(fixedRentalRate * getTotalWeeksCount(toBeMultiplied)).toFixed(2));
                    }
                                            
                    if (!isNaN($('#total_tax').val())) {
                        let thisCurrentTax = parseFloat($('#total_tax').val());
                        $('#total').val(parseFloat( newAmount + thisCurrentTax).toFixed(2));
                        $('#grand-zero-total').text(parseFloat( newAmount + thisCurrentTax).toFixed(2));
                    } else {
                        $('#total').val(newAmount);   
                        $('#grand-zero-total').text(newAmount);   
                    }
                }

            });

            $('#orderDate').datetimepicker({
                format:'d-m-Y H:i'
            });

            $('#frequency_time').datetimepicker({
                datepicker:false,
                format:'H:i',
                onChangeDateTime: function(dp, $input){
                    let fromDate = $('#from').val();
                    let tempFirstRemovalDate = $('#removal').val();
                    let toDate = $('#to').val();
                    
                    if (!$('#isfreq').is(':checked')) {
                        $('#deliver').val($('#from').val());
                        $('#removal').val($('#to').val());
                    }

                    if (typeof fromDate === 'string' && typeof toDate === 'string') {
                        let fromParts = fromDate.split(/[- :]/);
                        let toParts = toDate.split(/[- :]/);

                        let startDate = new Date(fromParts[2], fromParts[1] - 1, fromParts[0], fromParts[3], fromParts[4]);
                        let endDate = new Date(toParts[2], toParts[1] - 1, toParts[0], toParts[3], toParts[4]);

                        let differenceInMs = endDate - startDate;

                        let differenceInDays = differenceInMs / (1000 * 60 * 60 * 24);

                        differenceInDays = Math.floor(differenceInDays);
                        
                        $('#days').val(differenceInDays >= 0 ? differenceInDays + 1 : differenceInDays);
                    } else {
                        $('#days').val(0);
                    }

                    let tempFreqRes = Frequency.acceptableFrequencies(tempFirstRemovalDate, toDate, $('#frequency_time').val());
                    let oldFreqVal = $('#frequency').val();

                    if (Object.keys(tempFreqRes).length > 0) {
                        $('#frequency').html('');

                        for (singleOpt in tempFreqRes) {
                            $('#frequency').append(`<option value="${singleOpt}"> ${tempFreqRes[singleOpt].name} </option>`);
                        }

                        if (!isNaN(oldFreqVal) && oldFreqVal >= 1 && oldFreqVal <= 8) {
                            $('#frequency').val(oldFreqVal).trigger('change');
                        }
                    }

                    calculateAmount();
                },
                onShow: function() {
                    document.querySelectorAll('.xdsoft_datetimepicker').forEach(function(container) {
                        const datepicker = container.querySelector('.xdsoft_datepicker');
                        if (datepicker && !datepicker.classList.contains('active')) {
                            if (!container.classList.contains('no-active-datepicker-only-timepicker')) {
                                container.classList.add('no-active-datepicker-only-timepicker');
                            }
                        }
                    });
                }
            });
            
            $('#frequency_time').on('input', function () {
                let fromDate = $('#from').val();
                let tempFirstRemovalDate = $('#removal').val();
                let toDate = $('#to').val();
                
                if (!$('#isfreq').is(':checked')) {
                    $('#deliver').val($('#from').val());
                    $('#removal').val($('#to').val());
                }

                if (typeof fromDate === 'string' && typeof toDate === 'string') {
                    let fromParts = fromDate.split(/[- :]/);
                    let toParts = toDate.split(/[- :]/);

                    let startDate = new Date(fromParts[2], fromParts[1] - 1, fromParts[0], fromParts[3], fromParts[4]);
                    let endDate = new Date(toParts[2], toParts[1] - 1, toParts[0], toParts[3], toParts[4]);

                    let differenceInMs = endDate - startDate;

                    let differenceInDays = differenceInMs / (1000 * 60 * 60 * 24);

                    differenceInDays = Math.floor(differenceInDays);
                    
                    $('#days').val(differenceInDays >= 0 ? differenceInDays + 1 : differenceInDays);
                } else {
                    $('#days').val(0);
                }

                let tempFreqRes = Frequency.acceptableFrequencies(tempFirstRemovalDate, toDate, $('#frequency_time').val());
                let oldFreqVal = $('#frequency').val();

                if (Object.keys(tempFreqRes).length > 0) {
                    $('#frequency').html('');

                    for (singleOpt in tempFreqRes) {
                        $('#frequency').append(`<option value="${singleOpt}"> ${tempFreqRes[singleOpt].name} </option>`);
                    }

                    if (!isNaN(oldFreqVal) && oldFreqVal >= 1 && oldFreqVal <= 8) {
                        $('#frequency').val(oldFreqVal).trigger('change');
                    }
                }

                calculateAmount();
            });

            @if(!$newRentalCompleted)
            $('#from').datetimepicker({
                format:'d-m-Y H:i',
                onChangeDateTime: function(dp, $input){
                    let fromDate = $input.val();
                    let tempFirstRemovalDate = $('#removal').val();
                    let toDate = $('#to').val();
                    
                    $('#deliver').val($('#from').val());

                    if (!$('#isfreq').is(':checked')) {
                        $('#removal').val($('#to').val());
                    } else {
                        let thisFrmDate = $input.val();

                        dateToBeSet = moment(thisFrmDate, "DD-MM-YYYY HH:mm").format('DD-MM-YYYY');
                        TimeToBeSet = moment(thisFrmDate, "DD-MM-YYYY HH:mm").format('HH:mm');

                        $('#deliver').val(`${dateToBeSet} ${TimeToBeSet}`);
                    }

                    if ($('#contracttype option:selected').val() == 2) {
                        let considerThisStartDate = moment($('#from').val(), "DD-MM-YYYY HH:mm").format('DD-MM-YYYY HH:mm');

                        $('#deliver').val(considerThisStartDate);
                        $('#removal').val(considerThisStartDate);

                        if ($('#perpatual').is(':checked')) {
                            let tempFirstRemovalDate = $('#from').val();
                            theSevenDaysLater = moment(tempFirstRemovalDate, "DD-MM-YYYY HH:mm").endOf('month').format('DD-MM-YYYY HH:mm');
                            $('#to').val(theSevenDaysLater);
                        }
                    } else {
                        // if ($('#perpatual').is(':checked')) {
                        //     let tempFirstRemovalDate = $('#removal').val();
                        //     theSevenDaysLater = moment(tempFirstRemovalDate, "DD-MM-YYYY HH:mm").endOf('month').format('DD-MM-YYYY HH:mm');
                        // }
                    }                    

                    timeToBeSetOnly = moment(fromDate, "DD-MM-YYYY HH:mm").format('DD-MM-YYYY');
                    $input.val(`${timeToBeSetOnly} 00:00`)

                    if (typeof fromDate === 'string' && typeof toDate === 'string') {
                        let fromParts = fromDate.split(/[- :]/);
                        let toParts = toDate.split(/[- :]/);

                        let startDate = new Date(fromParts[2], fromParts[1] - 1, fromParts[0], fromParts[3], fromParts[4]);
                        let endDate = new Date(toParts[2], toParts[1] - 1, toParts[0], toParts[3], toParts[4]);

                        let differenceInMs = endDate - startDate;

                        let differenceInDays = differenceInMs / (1000 * 60 * 60 * 24);

                        differenceInDays = Math.floor(differenceInDays);
                        
                        $('#days').val(differenceInDays >= 0 ? differenceInDays + 1 : differenceInDays);
                    } else {
                        $('#days').val(0);
                    }

                    let tempFreqRes = Frequency.acceptableFrequencies(tempFirstRemovalDate, toDate, $('#frequency_time').val());
                    let oldFreqVal = $('#frequency').val();

                    if (Object.keys(tempFreqRes).length > 0) {
                        $('#frequency').html('');

                        for (singleOpt in tempFreqRes) {
                            $('#frequency').append(`<option value="${singleOpt}"> ${tempFreqRes[singleOpt].name} </option>`);
                        }

                        if (!isNaN(oldFreqVal) && oldFreqVal >= 1 && oldFreqVal <= 8) {
                            $('#frequency').val(oldFreqVal).trigger('change');
                        }
                    }

                    calculateAmount();
                }
            });
            @endif

            $('#from').on('input', function () {
                let fromDate = $(this).val();
                let tempFirstRemovalDate = $('#removal').val();
                let toDate = $('#to').val();

                $('#deliver').val($('#from').val());

                if (!$('#isfreq').is(':checked')) {
                    $('#removal').val($('#to').val());
                } else {
                    let thisFrmDate = fromDate;

                    dateToBeSet = moment(thisFrmDate, "DD-MM-YYYY HH:mm").format('DD-MM-YYYY');
                    TimeToBeSet = moment(thisFrmDate, "DD-MM-YYYY HH:mm").format('HH:mm');

                    $('#deliver').val(`${dateToBeSet} ${TimeToBeSet}`);
                }
                
                if ($('#contracttype option:selected').val() == 2) {
                    let considerThisStartDate = moment($('#from').val(), "DD-MM-YYYY HH:mm").format('DD-MM-YYYY HH:mm');

                    $('#deliver').val(considerThisStartDate);
                    $('#removal').val(considerThisStartDate);

                    if ($('#perpatual').is(':checked')) {
                        let tempFirstRemovalDate = $('#from').val();
                        theSevenDaysLater = moment(tempFirstRemovalDate, "DD-MM-YYYY HH:mm").endOf('month').format('DD-MM-YYYY HH:mm');
                        $('#to').val(theSevenDaysLater);
                    }
                } else {
                    // if ($('#perpatual').is(':checked')) {
                    //     let tempFirstRemovalDate = $('#removal').val();
                    //     theSevenDaysLater = moment(tempFirstRemovalDate, "DD-MM-YYYY HH:mm").endOf('month').format('DD-MM-YYYY HH:mm');
                    // }
                }

                timeToBeSetOnly = moment(fromDate, "DD-MM-YYYY HH:mm").format('DD-MM-YYYY');
                $(this).val(`${timeToBeSetOnly} 00:00`)

                if (typeof fromDate === 'string' && typeof toDate === 'string') {
                    let fromParts = fromDate.split(/[- :]/);
                    let toParts = toDate.split(/[- :]/);

                    let startDate = new Date(fromParts[2], fromParts[1] - 1, fromParts[0], fromParts[3], fromParts[4]);
                    let endDate = new Date(toParts[2], toParts[1] - 1, toParts[0], toParts[3], toParts[4]);

                    let differenceInMs = endDate - startDate;

                    let differenceInDays = differenceInMs / (1000 * 60 * 60 * 24);

                    differenceInDays = Math.floor(differenceInDays);
                    
                    $('#days').val(differenceInDays >= 0 ? differenceInDays + 1 : differenceInDays);
                } else {
                    $('#days').val(0);
                }

                let tempFreqRes = Frequency.acceptableFrequencies(tempFirstRemovalDate, toDate, $('#frequency_time').val());
                let oldFreqVal = $('#frequency').val();

                if (Object.keys(tempFreqRes).length > 0) {
                    $('#frequency').html('');

                    for (singleOpt in tempFreqRes) {
                        $('#frequency').append(`<option value="${singleOpt}"> ${tempFreqRes[singleOpt].name} </option>`);
                    }

                    if (!isNaN(oldFreqVal) && oldFreqVal >= 1 && oldFreqVal <= 8) {
                        $('#frequency').val(oldFreqVal).trigger('change');
                    }
                }

                calculateAmount();
            });

            $('#to').datetimepicker({
                format:'d-m-Y H:i',
                onChangeDateTime: function(dp, $input){
                    let fromDate = $('#from').val();
                    let tempFirstRemovalDate = $('#removal').val();
                    let toDate = $input.val();
                    
                    if (!$('#isfreq').is(':checked')) {
                        $('#deliver').val($('#from').val());
                        $('#removal').val($('#to').val());
                    }

                    timeToBeSetOnly = moment(toDate, "DD-MM-YYYY HH:mm").format('DD-MM-YYYY');
                    $input.val(`${timeToBeSetOnly} 23:59`)                    

                    if (typeof fromDate === 'string' && typeof toDate === 'string') {
                        let fromParts = fromDate.split(/[- :]/);
                        let toParts = toDate.split(/[- :]/);

                        let startDate = new Date(fromParts[2], fromParts[1] - 1, fromParts[0], fromParts[3], fromParts[4]);
                        let endDate = new Date(toParts[2], toParts[1] - 1, toParts[0], toParts[3], toParts[4]);

                        let differenceInMs = endDate - startDate;

                        let differenceInDays = differenceInMs / (1000 * 60 * 60 * 24);

                        differenceInDays = Math.floor(differenceInDays);
                        
                        $('#days').val(differenceInDays >= 0 ? differenceInDays + 1 : differenceInDays);
                    } else {
                        $('#days').val(0);
                    }

                    let tempFreqRes = Frequency.acceptableFrequencies(tempFirstRemovalDate, toDate, $('#frequency_time').val());
                    let oldFreqVal = $('#frequency').val();

                    if (Object.keys(tempFreqRes).length > 0) {
                        $('#frequency').html('');

                        for (singleOpt in tempFreqRes) {
                            $('#frequency').append(`<option value="${singleOpt}"> ${tempFreqRes[singleOpt].name} </option>`);
                        }

                        if (!isNaN(oldFreqVal) && oldFreqVal >= 1 && oldFreqVal <= 8) {
                            $('#frequency').val(oldFreqVal).trigger('change');
                        }
                    }

                    calculateAmount();
                }
            });

            $('#to').on('input', function () {
                let fromDate = $('#from').val();
                let tempFirstRemovalDate = $('#removal').val();
                let toDate = $(this).val();

                if (!$('#isfreq').is(':checked')) {
                    $('#deliver').val($('#from').val());
                    $('#removal').val($('#to').val());
                }

                timeToBeSetOnly = moment(toDate, "DD-MM-YYYY HH:mm").format('DD-MM-YYYY');
                $(this).val(`${timeToBeSetOnly} 23:59`)

                if (typeof fromDate === 'string' && typeof toDate === 'string') {
                    let fromParts = fromDate.split(/[- :]/);
                    let toParts = toDate.split(/[- :]/);

                    let startDate = new Date(fromParts[2], fromParts[1] - 1, fromParts[0], fromParts[3], fromParts[4]);
                    let endDate = new Date(toParts[2], toParts[1] - 1, toParts[0], toParts[3], toParts[4]);

                    let differenceInMs = endDate - startDate;

                    let differenceInDays = differenceInMs / (1000 * 60 * 60 * 24);

                    differenceInDays = Math.floor(differenceInDays);
                    
                    $('#days').val(differenceInDays >= 0 ? differenceInDays + 1 : differenceInDays);
                } else {
                    $('#days').val(0);
                }

                let tempFreqRes = Frequency.acceptableFrequencies(tempFirstRemovalDate, toDate, $('#frequency_time').val());
                let oldFreqVal = $('#frequency').val();

                if (Object.keys(tempFreqRes).length > 0) {
                    $('#frequency').html('');

                    for (singleOpt in tempFreqRes) {
                        $('#frequency').append(`<option value="${singleOpt}"> ${tempFreqRes[singleOpt].name} </option>`);
                    }

                    if (!isNaN(oldFreqVal) && oldFreqVal >= 1 && oldFreqVal <= 8) {
                        $('#frequency').val(oldFreqVal).trigger('change');
                    }
                }

                calculateAmount();
            });

            @if(!$newRentalCompleted)
            $('#deliver').datetimepicker({
                format:'d-m-Y H:i',
                onChangeDateTime: function(dp, $input){
                    let thisDeliverDate = $input.val();

                    dateToBeSet = moment(thisDeliverDate, "DD-MM-YYYY HH:mm").format('DD-MM-YYYY');
                    TimeToBeSet = '00:00';

                    $('#from').val(`${dateToBeSet} ${TimeToBeSet}`);

                    calculateAmount();
                }
            });
            @endif            

            $('#deliver').on('input', function () {
                let thisDeliverDate = $(this).val();

                dateToBeSet = moment(thisDeliverDate, "DD-MM-YYYY HH:mm").format('DD-MM-YYYY');
                TimeToBeSet = '00:00';

                $('#from').val(`${dateToBeSet} ${TimeToBeSet}`);

                calculateAmount();
            });

            $('#check_days').on('change', function () {
                if ($(this).is(':checked')) {
                    $('.show-only-frequency').addClass('d-none');
                    $('.show-only-days').removeClass('d-none');
                } else {
                    $('.show-only-frequency').removeClass('d-none');
                    $('.show-only-days').addClass('d-none');
                    $('.show-only-days input[type="checkbox"]').prop('checked', false);
                }

                calculateAmount();
            });

            @if(!$firstRemovalCompleted)
            $('#removal').datetimepicker({
                format:'d-m-Y H:i',
                onChangeDateTime: function(dp, $input){
                    let tempFirstRemovalDate = $('#removal').val();
                    let toDate = $('#to').val();
                    theSevenDaysLater = moment(tempFirstRemovalDate, "DD-MM-YYYY HH:mm").endOf('month').format('DD-MM-YYYY HH:mm');

                    let tempFreqRes = Frequency.acceptableFrequencies(tempFirstRemovalDate, toDate, $('#frequency_time').val());
                    let oldFreqVal = $('#frequency').val();

                    if (Object.keys(tempFreqRes).length > 0) {
                        $('#frequency').html('');

                        for (singleOpt in tempFreqRes) {
                            $('#frequency').append(`<option value="${singleOpt}"> ${tempFreqRes[singleOpt].name} </option>`);
                        }

                        if (!isNaN(oldFreqVal) && oldFreqVal >= 1 && oldFreqVal <= 8) {
                            $('#frequency').val(oldFreqVal).trigger('change');
                        }
                    }

                    calculateAmount();
                }
            });
            @endif

            $('#removal').on('input', function () {
                let toDate = $('#to').val();
                let tempFirstRemovalDate = $(this).val();
                theSevenDaysLater = moment(tempFirstRemovalDate, "DD-MM-YYYY HH:mm").endOf('month').format('DD-MM-YYYY HH:mm');

                let tempFreqRes = Frequency.acceptableFrequencies(tempFirstRemovalDate, toDate, $('#frequency_time').val());
                let oldFreqVal = $('#frequency').val();

                if (Object.keys(tempFreqRes).length > 0) {
                    $('#frequency').html('');

                    for (singleOpt in tempFreqRes) {
                        $('#frequency').append(`<option value="${singleOpt}"> ${tempFreqRes[singleOpt].name} </option>`);
                    }

                    if (!isNaN(oldFreqVal) && oldFreqVal >= 1 && oldFreqVal <= 8) {
                        $('#frequency').val(oldFreqVal).trigger('change');
                    }
                }

                calculateAmount();
            });

            $(document).on('change', '#flat_rate_check', function () {
                if ($(this).is(':checked')) {
                    $('#total_rental').prop('readonly', false);
                } else {
                    $('#total_rental').prop('readonly', true).val($('#old_total_rental').val());

                    let thisRental = parseFloat($('#total_rental').val());
                    let totalTax = parseFloat($('#total_tax').val());
                    let selectedTax = parseFloat($('option:selected', '#tax').attr('data-rate'));
                    let newGrandTotal = thisRental;
                    let newTax = 0;

                    if (selectedTax >= 0) {
                        newTax = parseFloat((thisRental * selectedTax) / 100);
                        newGrandTotal += newTax;
                    }

                    $('#total_rental').val(thisRental.toFixed(2));
                    $('#total_tax').val(newTax.toFixed(2));
                    $('#total').val(parseFloat(newGrandTotal).toFixed(2));
                }
            });

            initializeFrequencySelect2();
            initializeCustomerSelect2();
            initializeLocationSelect2();
            initializeRequestMethodSelect2();
            initializeSkipSelect2();
            initializeWasteTypeSelect2();
            initializePriceLevelSelect2();
            initializeCompanySelect2();
            initializeRequestedBySelect2();
            initializeTaxSelect2();
            initializePayMethodFeeType();
            initializeTippingFeeType();
            initializeStatusSelect2();
            initializeContractTypeSelect2();
            initializePaymentTermSelect2();
            initializeVehicleSelect2();
            initializeServiceTypeSelect2();
            initializeDriverSelect2();
            initializeSecondManSelect2();            
        });
    </script>
@endpush
