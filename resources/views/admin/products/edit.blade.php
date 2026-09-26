@extends('layouts.admin')

@section('title', 'Sửa sản phẩm')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Sửa: {{ $product->name }}</h4>
        <p class="text-muted small mb-0">
            Mã #{{ $product->id }}{{ $product->sku ? ' · SKU ' . $product->sku : '' }} ·
            Cập nhật lần cuối {{ optional($product->updated_at)->format('d/m/Y H:i') }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('products.show', $product->id) }}" target="_blank" class="btn btn-outline-primary rounded-pill px-4">
            <i class="bi bi-box-arrow-up-right me-1"></i> Xem trang khách
        </a>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Quay lại
        </a>
    </div>
</div>

<form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.products._form', ['product' => $product, 'categories' => $categories])

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-check-lg me-1"></i> Cập nhật
        </button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy bỏ</a>
    </div>
</form>

{{-- Các form xóa ảnh phụ, đặt NGOÀI form sản phẩm vì HTML không cho lồng form.
     Nút xóa trong _form.blade.php trỏ tới đây bằng thuộc tính form="del-image-{id}". --}}
@foreach ($product->images as $image)
<form id="del-image-{{ $image->id }}" method="POST"
      action="{{ route('admin.products.images.destroy', [$product->id, $image->id]) }}"
      onsubmit="return confirm('Xóa ảnh này khỏi thư viện?')">
    @csrf @method('DELETE')
</form>
@endforeach
@endsection
