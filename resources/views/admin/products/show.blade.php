@extends('layouts.admin')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4 mx-auto" style="max-width: 900px;">
    <!-- Tiêu đề & Trạng thái Kho -->
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h4 class="text-secondary fw-bold mb-0">Chi Tiết Sản Phẩm</h4>
        <div>
            <!-- Đổi $category thành $product -->
            @if($product->quantity > 0)
            <span class="badge bg-success fs-6 px-3 py-2 rounded-pill shadow-sm">Còn hàng ({{ $product->quantity }})</span>
            @else
            <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill shadow-sm">Tạm hết hàng</span>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Cột Trái: Khu vực Ảnh và Giá -->
        <div class="col-md-4 text-center mb-4 mb-md-0">
            @if($product->image)
            <img src="{{ $product->image_url }}" class="img-fluid rounded-4 shadow-sm border p-1" alt="{{ $product->name }}" style="max-height: 300px; object-fit: cover;">
            @else
            <div class="bg-light rounded-4 d-flex align-items-center justify-content-center border" style="height: 250px;">
                <span class="text-muted fs-5">Chưa cập nhật ảnh</span>
            </div>
            @endif

            <!-- Bảng giá to, nổi bật -->
            <div class="mt-4 p-3 bg-light rounded-3 text-center border border-danger border-opacity-25 shadow-sm">
                <div class="text-muted small mb-1 fw-bold">GIÁ BÁN CHÍNH THỨC</div>
                <div class="text-danger fw-bold fs-3">{{ number_format($product->price, 0, ',', '.') }} VNĐ</div>
            </div>
        </div>

        <!-- Cột Phải: Thông tin chi tiết -->
        <div class="col-md-8">
            <h5 class="fw-bold mb-1">{{ $product->name }}</h5>
            <div class="mb-3">
                <span class="badge rounded-pill tl-badge-cream">
                    {{ $product->category->icon ?? '' }} {{ $product->category->full_name ?? 'Chưa phân loại' }}
                </span>
                @if ($product->is_featured)
                <span class="badge rounded-pill bg-primary ms-1">Nổi bật</span>
                @endif
            </div>

            @include('partials._product-specs', ['product' => $product])

            <div class="mb-4">
                <div class="fw-semibold text-muted mb-2">Mô tả sản phẩm:</div>
                <div class="bg-light p-3 rounded-3 border">
                    {{ $product->description ?? 'Chưa có thông tin chi tiết.' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Nút hành động -->
    <div class="mt-4 pt-3 border-top text-end">
        <!-- Đổi route thành products.index và products.edit -->
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Quay lại danh sách</a>
        <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-primary rounded-pill px-4 shadow-sm ms-2">
            Chỉnh sửa ngay
        </a>
    </div>
</div>
@endsection