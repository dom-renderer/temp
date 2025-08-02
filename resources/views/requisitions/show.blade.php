@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Requisition Details</h4>
                    </div>
                    <div class="col-md-6 text-end">
                        @if(auth()->user()->can('requisitions.edit'))
                            @if($requisition->status == 'PENDING')
                                <a href="{{ route('requisitions.edit', encrypt($requisition->id)) }}" class="btn btn-primary">
                                    <i class="fa fa-edit"></i> Edit Requisition
                                </a>
                            @endif
                        @endif
                        <a href="{{ route('requisitions.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Requisition Code:</strong></td>
                                <td>{{ $requisition->code ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Job:</strong></td>
                                <td>{{ $requisition->job->code ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    @switch($requisition->status)
                                        @case('PENDING')
                                            <span class="badge bg-warning">Pending</span>
                                            @break
                                        @case('APPROVED')
                                            <span class="badge bg-success">Approved</span>
                                            @break
                                        @case('REJECTED')
                                            <span class="badge bg-danger">Rejected</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">Unknown</span>
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Created At:</strong></td>
                                <td>{{ $requisition->created_at->format('d-m-Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Updated At:</strong></td>
                                <td>{{ $requisition->updated_at->format('d-m-Y H:i:s') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Added By:</strong></td>
                                <td>{{ $requisition->addedBy->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Items:</strong></td>
                                <td>{{ $requisition->items->count() }}</td>
                            </tr>
                            <tr>
                                <td><strong>Grand Total:</strong></td>
                                <td><strong>{{ number_format($requisition->items->sum('total'), 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <h5>Requisition Items</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Type</th>
                                        <th>Product</th>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Amount</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requisition->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if($item->type == 'INVENTORY')
                                                <span class="badge bg-info">Inventory</span>
                                            @else
                                                <span class="badge bg-warning">Vendor</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->type == 'INVENTORY')
                                                {{ $item->product->name ?? 'N/A' }}
                                            @else
                                                <strong> VENDOR :</strong> <span>
                                                    {{ $item->vendor->name ?? '' }}
                                                </span>
                                                <br><br>
                                                {{ $item->product_id ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ $item->description ?? 'N/A' }}</td>
                                        <td>{{ $item->quantity ?? '0' }}</td>
                                        <td>{{ number_format($item->amount, 2) ?? '0.00' }}</td>
                                        <td><strong>{{ number_format($item->total, 2) ?? '0.00' }}</strong></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No items found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-end"><strong>Grand Total:</strong></td>
                                        <td><strong>{{ number_format($requisition->items->sum('total'), 2) }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                @if($requisition->status == 'APPROVED')
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Approval Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Approved By:</strong></td>
                                    <td>{{ $requisition->approvedBy->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Approved At:</strong></td>
                                    <td>{{ $requisition->approved_at ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Approval Reason:</strong></td>
                                    <td>{{ $requisition->approved_reason ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                @endif

                @if($requisition->status == 'REJECTED')
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Rejection Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Rejected By:</strong></td>
                                    <td>{{ $requisition->rejectedBy->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Rejected At:</strong></td>
                                    <td>{{ $requisition->rejected_at ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Rejection Reason:</strong></td>
                                    <td>{{ $requisition->rejected_reason ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 