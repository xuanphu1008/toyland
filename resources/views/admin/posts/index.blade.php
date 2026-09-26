@extends('layouts.admin')

@section('title', 'Bài viết')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-secondary fw-bold mb-0">Quản Lý Bài Viết / Blog</h5>
    <a href="{{ route('admin.posts.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">+ Viết bài mới</a>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <form action="{{ route('admin.posts.index') }}" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-6"><input type="text" name="search" class="form-control" placeholder="Tìm theo tiêu đề..." value="{{ request('search') }}"></div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">-- Tất cả chuyên mục --</option>
                    @foreach ($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-secondary w-100">Lọc</button></div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px;">Ảnh</th>
                        <th>Tiêu đề</th>
                        <th>Chuyên mục</th>
                        <th class="text-center">Lượt xem</th>
                        <th>Ngày đăng</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                    <tr>
                        <td>
                            @if ($post->image)
                            <img src="{{ $post->image_url }}" class="rounded border" width="50" height="50" style="object-fit: cover;" alt="">
                            @else
                            <span class="badge bg-secondary">--</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $post->title }}</div>
                            <div class="small text-muted">bởi {{ $post->author->name ?? 'ToyLand' }}</div>
                        </td>
                        <td><span class="badge bg-info text-dark">{{ $post->category }}</span></td>
                        <td class="text-center">{{ $post->views }}</td>
                        <td class="small text-nowrap">{{ optional($post->published_at)->format('d/m/Y H:i') }}</td>
                        <td class="text-center">
                            <span class="badge {{ $post->is_published ? 'bg-success' : 'bg-secondary' }}">{{ $post->is_published ? 'Đã đăng' : 'Nháp' }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('posts.show', $post->slug) }}" target="_blank" class="btn btn-outline-info btn-sm rounded-pill">Xem</a>
                            <a href="{{ route('admin.posts.edit', $post->slug) }}" class="btn btn-outline-secondary btn-sm rounded-pill">Sửa</a>
                            <form action="{{ route('admin.posts.destroy', $post->slug) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa bài viết này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm rounded-pill">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-muted text-center py-4">Chưa có bài viết nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $posts->links() }}</div>
    </div>
</div>
@endsection
