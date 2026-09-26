@extends('layouts.admin')

@section('title', 'Thêm sản phẩm')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Thêm Sản Phẩm Đồ Chơi</h4>
        <p class="text-muted small mb-0">Điền càng đủ thông tin, khách càng dễ tìm đúng món cho bé.</p>
    </div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left me-1"></i> Quay lại
    </a>
</div>

<form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.products._form', ['product' => null, 'categories' => $categories])

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-check-lg me-1"></i> Lưu sản phẩm
        </button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy bỏ</a>
    </div>
</form>
@endsection
