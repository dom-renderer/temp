@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Customer Details</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <img src="{{ $engineer->profile ? asset('storage/users/profile/' . $engineer->profile) : asset('assets/images/profile.png') }}" alt="Profile" class="img-thumbnail" width="120">
                    </div>
                    <div class="col-md-9">
                        <h4>{{ $engineer->name }}</h4>
                        @if($engineer->alternate_name)
                            <p><strong>Contact Name:</strong> {{ $engineer->alternate_name }}</p>
                        @endif
                        <p><strong>Email:</strong> {{ $engineer->email }}</p>
                        <p><strong>Phone:</strong> +{{ $engineer->alternate_dial_code }} {{ $engineer->alternate_phone_number }}</p>
                        <p><strong>Status:</strong> {!! $engineer->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</p>
                    </div>
                </div>
                
                @if($engineer->address_line_1 || $engineer->address_line_2 || $engineer->country || $engineer->state || $engineer->city || $engineer->pincode)
                <div class="row mb-3">
                    <div class="col-12">
                        <h5>Address Information</h5>
                        <div class="card">
                            <div class="card-body">
                                @if($engineer->address_line_1)
                                    <p><strong>Address Line 1:</strong> {{ $engineer->address_line_1 }}</p>
                                @endif
                                @if($engineer->address_line_2)
                                    <p><strong>Address Line 2:</strong> {{ $engineer->address_line_2 }}</p>
                                @endif
                                @if($engineer->city)
                                    <p><strong>City:</strong> {{ $engineer->cityr->name ?? '' }}</p>
                                @endif
                                @if($engineer->state)
                                    <p><strong>State:</strong> {{ $engineer->stater->name ?? '' }}</p>
                                @endif
                                @if($engineer->country)
                                    <p><strong>Country:</strong> {{ $engineer->countryr->name ?? '' }}</p>
                                @endif
                                @if($engineer->pincode)
                                    <p><strong>Pincode:</strong> {{ $engineer->pincode }}</p>
                                @endif
                                @if($engineer->latitude && $engineer->longitude)
                                    <p><strong>Location:</strong> {{ $engineer->latitude }}, {{ $engineer->longitude }}</p>
                                    @if($engineer->location_url)
                                        <p><strong>Location URL:</strong> <a href="{{ $engineer->location_url }}" target="_blank">{{ $engineer->location_url }}</a></p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                <a href="{{ route('customers.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
</div>
@endsection 