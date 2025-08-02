@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@push('css')
<link href="https://unpkg.com/filepond/dist/filepond.min.css" rel="stylesheet">
<link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">Manage Images for: {{ $product->name }}</div>
            <div class="card-body">
                <input type="file" class="filepond" name="images[]" id="product-images" multiple data-max-files="10">
                <div id="image-list" class="mt-4">
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-secondary mt-3">Back to List</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
<script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js"></script>
<script src="https://unpkg.com/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
FilePond.registerPlugin(FilePondPluginImagePreview);
const productId = {{ $product->id }};
const pond = FilePond.create(document.querySelector('#product-images'), {
    server: {
        process: {
            url: `{{ route('products.images.upload', $product->id) }}`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        },
        revert: null,
        load: null,
        fetch: null
    },
    allowMultiple: true,
    maxFiles: 10
});

function loadImages() {
    $.get(`{{ route('products.images.list', $product->id) }}`, function(images) {
        let html = '<ul id="sortable-images" class="list-group">';
        images.forEach(function(img) {
            html += `<li class='list-group-item d-flex align-items-center' data-id='${img.id}'>
                <a href='${img.url}' target='_blank'>
                    <img src='${img.url}' style='height:60px;width:60px;object-fit:cover;margin-right:10px;'>
                </a>
                <span class='flex-grow-1'>${img.file}</span>
                <button class='btn btn-danger btn-sm delete-image' data-id='${img.id}'>Delete</button>
            </li>`;
        });
        html += '</ul>';
        $('#image-list').html(html);
        new Sortable(document.getElementById('sortable-images'), {
            animation: 150,
            onEnd: function () {
                let order = [];
                $('#sortable-images li').each(function() {
                    order.push($(this).data('id'));
                });
                $.post(`{{ route('products.images.sort', $product->id) }}`, { order: order, _token: '{{ csrf_token() }}' });
            }
        });
    });
}

$(document).on('click', '.delete-image', function() {
    let id = $(this).data('id');

    Swal.fire({
        title: 'Are you sure?',
        text: 'You won\'t be able to revert this!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ url('products') }}/{{ $product->id }}/images/${id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function() { loadImages(); }
            });
        }
    });
});

pond.on('processfile', function() { loadImages(); });

$(function() { loadImages(); });
</script>
@endpush 