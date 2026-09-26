@extends('layouts.admin')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="text-secondary fw-bold mb-0">Quản Lý Danh Mục</h5>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                + Thêm Mới
            </a>
        </div>

        <form action="{{ route('admin.categories.index') }}" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Tìm theo tên danh mục..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="level" class="form-select">
                    <option value="">-- Tất cả cấp --</option>
                    <option value="root" {{ request('level') === 'root' ? 'selected' : '' }}>Chỉ danh mục gốc</option>
                    <option value="child" {{ request('level') === 'child' ? 'selected' : '' }}>Chỉ danh mục con</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100">Lọc</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">Tên Danh Mục</th>
                        <th class="text-start">Thuộc Danh Mục Cha</th>
                        <th>Danh Mục Con</th>
                        <th>Số Sản Phẩm</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                    <tr>
                        <td class="text-start fw-bold text-dark">
                            {{ $category->icon }} {{ $category->name }}
                            <div class="small text-muted fw-normal">{{ Str::limit($category->description, 60) ?: '—' }}</div>
                        </td>
                        <td class="text-start text-muted">
                            @if ($category->parent)
                            {{ $category->parent->name }}
                            @else
                            <span class="badge tl-badge-cream">Danh mục gốc</span>
                            @endif
                        </td>
                        <td>{{ $category->children_count ?: '—' }}</td>
                        <td><span class="badge bg-info text-dark">{{ $category->products_count }}</span></td>
                        <td>
                            <a class="btn btn-outline-secondary btn-sm rounded-pill" href="{{ route('admin.categories.edit', $category->id) }}">Sửa</a>
                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill" onclick="return confirm('Bạn có muốn xóa danh mục này?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-muted py-4">Chưa có danh mục nào!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection
