@extends('layouts.app',['title' => $title, 'subTitle' => $subTitle])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Notification Template Details</h5>
                <div class="float-end">
                    @if(auth()->user()->can('notification-templates.edit'))
                        <a href="{{ route('notification-templates.edit', encrypt($template->id)) }}" class="btn btn-primary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                    @endif
                    <a href="{{ route('notification-templates.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title:</label>
                            <p class="form-control-plaintext">{{ $template->title }}</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status:</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-{{ $template->status === 'ACTIVE' ? 'success' : 'danger' }}">
                                    {{ $template->status }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Notification Types:</label>
                    <div class="form-control-plaintext">
                        @foreach($template->type as $type)
                            <span class="badge bg-primary me-1">{{ ucwords($type) }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Body Content:</label>
                    <div class="border rounded p-3 bg-light">
                        {!! $template->body !!}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Created At:</label>
                            <p class="form-control-plaintext">{{ $template->created_at->format('d M Y, h:i A') }}</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Last Updated:</label>
                            <p class="form-control-plaintext">{{ $template->updated_at->format('d M Y, h:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection 