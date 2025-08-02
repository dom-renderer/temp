@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Product Details</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h4>{{ $product->name }}</h4>
                        <p><strong>Category:</strong> {{ $product->category ? $product->category->name : 'None' }}</p>
                        <p><strong>SKU:</strong> {{ $product->sku }}</p>
                        <p><strong>Description 1:</strong> {{ $product->description_1 }}</p>
                        <p><strong>Description 2:</strong> {{ $product->description_2 }}</p>
                        <p><strong>Amount:</strong> {{ $product->amount }}</p>
                        <p><strong>Status:</strong> {!! $product->status ? '<span class="badge bg-success">Enable</span>' : '<span class="badge bg-danger">Disable</span>' !!}</p>
                    </div>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
</div>
@endsection 