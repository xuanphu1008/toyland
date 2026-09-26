@extends('layouts.admin')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="text-secondary fw-bold mb-0">Quản Lý Sản Phẩm Đồ Chơi</h5>
            <!-- Đổi route thành products.create -->
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                + Thêm Mới
            </a>
        </div>

        <!-- Thanh Tìm kiếm và Lọc (Đổi route thành products.index) -->
        <form action="{{ route('admin.products.index') }}" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Tìm tên, thương hiệu, chất liệu, xuất xứ..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">-- Tất cả danh mục --</option>
                    @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->parent_id ? '— ' : '' }}{{ $category->icon }} {{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- Tất cả trạng thái kho --</option>
                    <option value="in_stock" {{ request('status') == 'in_stock' ? 'selected' : '' }}>Còn hàng</option>
                    <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Hết hàng</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Lọc</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>Ảnh</th>
                        <th class="text-start">Tên Sản Phẩm</th>
                        <th>Danh Mục</th> <!-- Cột mới thêm để hiển thị tên danh mục -->
                        <th>Giá Bán</th>
                        <th>Kho</th>
                        <th>Đã bán</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Đổi biến thành $products và $product -->
                    @forelse ($products as $product)
                    <tr>
                        <td>
                            @if($product->image)
                            <img src="{{ $product->image_url }}" class="rounded border" width="50" height="50" style="object-fit: cover;">
                            @else
                            <span class="badge bg-secondary">No Image</span>
                            @endif
                        </td>
                        <td class="text-start fw-bold text-dark">
                            {{ $product->name }}
                            <div class="small text-muted fw-normal">
                                {{ $product->brand ?: 'Chưa có hãng' }}
                                @if ($product->sku) · {{ $product->sku }} @endif
                                @if ($product->age_label) · {{ $product->age_label }} @endif
                            </div>
                        </td>

                        <!-- Hiển thị tên danh mục nhờ vào quan hệ 1-N đã tạo trong Model -->
                        <td>
                            <span class="badge bg-info text-dark">{{ $product->category->name ?? 'Chưa phân loại' }}</span>
                            @if (optional($product->category)->parent)
                            <div class="small text-muted">{{ $product->category->parent->name }}</div>
                            @endif
                        </td>

                        <td class="text-danger fw-bold">{{ number_format($product->price, 0, ',', '.') }} đ</td>
                        <td>{{ $product->quantity }}</td>
                        <td><span class="badge bg-light text-dark">{{ (int) $product->sold_quantity }}</span></td>
                        <td>
                            @if($product->quantity > 0)
                            <span class="badge bg-success">Còn hàng</span>
                            @else
                            <span class="badge bg-danger">Hết hàng</span>
                            @endif
                        </td>
                        <td>
                            <!-- Đổi toàn bộ route hành động sang products.* -->
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST">
                                <a class="btn btn-outline-info btn-sm rounded-pill" href="{{ route('admin.products.show', $product->id) }}">Xem</a>
                                <a class="btn btn-outline-secondary btn-sm rounded-pill mx-1" href="{{ route('admin.products.edit', $product->id) }}">Sửa</a>
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill" onclick="return confirm('Bạn có muốn xóa?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <!-- Chỉnh colspan thành 7 vì đã thêm 1 cột Danh Mục -->
                        <td colspan="8" class="text-muted py-4">Không tìm thấy sản phẩm nào!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            <!-- Đổi biến phân trang -->
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection