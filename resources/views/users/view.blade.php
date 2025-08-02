@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">User Details</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <a href="{{  $user->userprofile  }}" target="_blank">
                            <img src="{{ $user->userprofile }}" alt="Profile" class="img-thumbnail" width="120">
                        </a>
                    </div>
                    <div class="col-md-9">
                        <h4>{{ $user->name }}</h4>
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <p><strong>Phone:</strong> {{ $user->dial_code }} {{ $user->phone_number }}</p>
                        <p><strong>Status:</strong> {!! $user->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</p>
                        <p><strong>Roles:</strong> @foreach($user->roles as $role) <span class="badge bg-primary">{{ $role->name }}</span> @endforeach</p>
                    </div>
                </div>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
</div>
@endsection
