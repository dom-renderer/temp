@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle, 'select2' => true])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Edit Product</div>
            <div class="card-body">
                <form id="productForm" method="POST" action="{{ route('products.update', encrypt($product->id)) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="category_id" name="category_id" required>
                            @if(isset($category->id))
                                <option value="{{ $category->id }}"> {{ $category->name }} </option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required>
                        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="description_1" class="form-label">Description 1</label>
                        <textarea class="form-control @error('description_1') is-invalid @enderror" id="description_1" name="description_1">{{ old('description_1', $product->description_1) }}</textarea>
                        @error('description_1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="description_2" class="form-label">Description 2</label>
                        <textarea class="form-control @error('description_2') is-invalid @enderror" id="description_2" name="description_2">{{ old('description_2', $product->description_2) }}</textarea>
                        @error('description_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $product->amount) }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="1" {{ old('status', $product->status) == '1' ? 'selected' : '' }}>Enable</option>
                            <option value="0" {{ old('status', $product->status) == '0' ? 'selected' : '' }}>Disable</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('#category_id').select2({
        allowClear: true,
        placeholder: 'Select a category',
        width: '100%',
        theme: 'classic',
        ajax: {
            url: "{{ route('product-category-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,  
                    _token: "{{ csrf_token() }}",
                    except: "{{ $product->id }}"
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
    $('#productForm').validate({
        rules: {
            category_id: { required: true },
            name: { required: true },
            sku: { required: true },
            amount: { required: true }
        },
        errorPlacement: function(error, element) {
            error.appendTo(element.parent());
        }
    });
});
</script>
@endpush 