@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Engineer Details</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <img src="{{ $engineer->profile ? asset('storage/users/profile/' . $engineer->profile) : asset('assets/images/profile.png') }}" alt="Profile" class="img-thumbnail" width="120">
                    </div>
                    <div class="col-md-9">
                        <h4>{{ $engineer->name }}</h4>
                        <p><strong>Email:</strong> {{ $engineer->email }}</p>
                        <p><strong>Phone:</strong> {{ $engineer->dial_code }} {{ $engineer->phone_number }}</p>
                        <p><strong>Status:</strong> {!! $engineer->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</p>
                        <p><strong>Departments:</strong> 
                        <ul>
                        @foreach ($currentDepartments as $row)
                            <strong>
                                <li>
                                    {{ $row->department->name ?? '' }}
                                </li>
                            </strong> <br/>
                        @endforeach
                        </ul>
                        </p>
                    </div>
                </div>
                <a href="{{ route('engineers.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
</div>
@endsection 