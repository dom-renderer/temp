@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle, 'select2' => true])

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Edit Category</div>
            <div class="card-body">
                <form id="categoryForm" method="POST" action="{{ route('categories.update', encrypt($category->id)) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category</label>
                        <select class="form-select select2" id="parent_id" name="parent_id">
                            @if(isset($parentCategory->id))
                                <option value="{{ $parentCategory->id }}"> {{ $parentCategory->name }} </option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label"> Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="1" {{ old('status', $category->status) == '1' ? 'selected' : '' }}>Enable</option>
                            <option value="0" {{ old('status', $category->status) == '0' ? 'selected' : '' }}>Disable</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancel</a>
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

    $('#parent_id').select2({
        allowClear: true,
        placeholder: 'Select a parent category',
        width: '100%',
        theme: 'classic',
        ajax: {
            url: "{{ route('category-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,  
                    _token: "{{ csrf_token() }}",
                    except: "{{ $category->id }}"
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

    $('#categoryForm').validate({
        rules: {
            name: { required: true },
            status: { required: true }
        },
        errorPlacement: function(error, element) {
            error.appendTo(element.parent());
        }
    });
});
</script>
@endpush 