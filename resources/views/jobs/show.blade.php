@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">{{ $subTitle }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('jobs.index') }}">Jobs</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Job Information</h4>
                        <div>
                            @if(auth()->user()->can('jobs.edit'))
                                @if(in_array($job->status, ['PENDING', 'INPROGRESS']))
                                    <a href="{{ route('jobs.edit', encrypt($job->id)) }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-edit"></i> Edit Job
                                    </a>
                                @endif
                            @endif
                            <a href="{{ route('jobs.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Job Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Job Details</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Job Code:</strong></td>
                                    <td>{{ $job->code }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Title:</strong></td>
                                    <td>{{ $job->title }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @php
                                            $statuses = [
                                                'PENDING' => '<span class="badge bg-warning">Pending</span>',
                                                'INPROGRESS' => '<span class="badge bg-info">In-Progress</span>',
                                                'COMPLETED' => '<span class="badge bg-success">Completed</span>',
                                                'CANCELLED' => '<span class="badge bg-danger">Cancelled</span>'
                                            ];
                                        @endphp
                                        {!! $statuses[$job->status] ?? '' !!}
                                    </td>
                                </tr>
                                @if($job->status == 'CANCELLED')
                                    <tr>
                                        <td><strong>Cancellation Reason:</strong></td>
                                        <td>{{ $job->cancellation_note }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cancellation Fees:</strong></td>
                                        <td>{{ number_format($job->cancellation_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>Opening Date:</strong></td>
                                    <td>{{ $job->opening_date ? \Carbon\Carbon::parse($job->opening_date)->format('d-m-Y H:i') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Visiting Date:</strong></td>
                                    <td>{{ $job->visiting_date ? \Carbon\Carbon::parse($job->visiting_date)->format('d-m-Y H:i') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Opened Date:</strong></td>
                                    <td>{{ $job->opened_date ? \Carbon\Carbon::parse($job->opened_date)->format('d-m-Y H:i') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Visited Date:</strong></td>
                                    <td>{{ $job->visited_date ? \Carbon\Carbon::parse($job->visited_date)->format('d-m-Y H:i') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Deposit Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Requires Deposit:</strong></td>
                                    <td>
                                        @if($job->requires_deposit)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($job->requires_deposit)
                                <tr>
                                    <td><strong>Deposit Type:</strong></td>
                                    <td>{{ ucfirst(strtolower($job->deposit_type)) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Deposit Amount:</strong></td>
                                    <td> @if($job->deposit_type == 'FIX') $ @endif {{ number_format($job->deposit_amount, 2) }} @if($job->deposit_type == 'PERCENT') % @endif </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- Description and Summary -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">Description & Summary</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Description:</h6>
                                    <p class="text-muted">{{ $job->description ?? 'No description provided' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Summary:</h6>
                                    <p class="text-muted">{{ $job->summary ?? 'No summary provided' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">Customer Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="150"><strong>Customer:</strong></td>
                                            <td>{{ $job->customer ? $job->customer->name : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Contact Name:</strong></td>
                                            <td>{{ $job->contact_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $job->email }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td>{{ $job->contact_dial_code ? '+' . $job->contact_dial_code . ' ' . $job->contact_phone_number : $job->contact_phone_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Billing Name:</strong></td>
                                            <td>{{ $job->billing_name }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="150"><strong>Assigner:</strong></td>
                                            <td>{{ $job->assigner ? $job->assigner->name : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td>{{ $job->created_at->format('d-m-Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expertises -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">Expertise Required</h5>
                            @if($job->expertise && $job->expertise->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($job->expertise as $index => $expertise)
                                                @if(isset($expertise->expertise->id))
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $expertise->expertise->name }}</td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No expertise selected for this job.
                                </div>
                            @endif
                        </div>
                    </div>
                    <!-- Expertises -->

                    <!-- Technicians -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">Assigned Technicians</h5>
                            @if($job->technicians && $job->technicians->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Time Spent at Job</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($job->technicians as $index => $technician)
                                                @if(isset($technician->technician->id))
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $technician->technician->name }}</td>
                                                    <td>{{ $technician->technician->email }}</td>
                                                    <td>{{ $technician->technician->dial_code ? '+' . $technician->technician->dial_code . ' ' . $technician->technician->phone_number : $technician->technician->phone_number }}</td>
                                                    <td>
                                                        {{ $times[$technician->technician_id]['total_time_spent'] ?? '00s' }}
                                                    </td>
                                                    <td>
                                                        @if($technician->technician->status)
                                                            <span class="badge bg-success">Enable</span>
                                                        @else
                                                            <span class="badge bg-danger">Disable</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No technicians assigned to this job.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Materials -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">Job Materials</h5>
                            @if($job->materials && $job->materials->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $totalAmount = 0; @endphp
                                            @foreach($job->materials as $index => $material)
                                                @php $totalAmount += $material->total; @endphp
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $material->product ? $material->product->name : 'N/A' }}</td>
                                                    <td>{{ $material->product && $material->product->category ? $material->product->category->name : 'N/A' }}</td>
                                                    <td>{{ $material->description ?? 'N/A' }}</td>
                                                    <td>{{ $material->quantity }}</td>
                                                    <td>${{ number_format($material->amount, 2) }}</td>
                                                    <td>${{ number_format($material->total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-primary">
                                                <th colspan="6" class="text-end">Total Materials Cost:</th>
                                                <th>${{ number_format($totalAmount, 2) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No materials added to this job.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 