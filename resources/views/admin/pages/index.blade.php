@extends('layouts.admin')

@section('title', 'Trang tĩnh')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-secondary fw-bold mb-0">Quản Lý Trang Tĩnh (CMS)</h5>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">+ Tạo trang mới</a>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px;">Thứ tự</th>
                        <th>Tiêu đề</th>
                        <th>Đường dẫn</th>
                        <th class="text-center">Chân trang</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                    <tr>
                        <td class="text-muted">{{ $page->sort_order }}</td>
                        <td class="fw-semibold">{{ $page->title }}</td>
                        <td><code class="small">/trang/{{ $page->slug }}</code></td>
                        <td class="text-center">{{ $page->show_in_footer ? '✔' : '—' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $page->is_published ? 'bg-success' : 'bg-secondary' }}">{{ $page->is_published ? 'Đã đăng' : 'Nháp' }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="btn btn-outline-info btn-sm rounded-pill">Xem</a>
                            <a href="{{ route('admin.pages.edit', $page->slug) }}" class="btn btn-outline-secondary btn-sm rounded-pill">Sửa</a>
                            <form action="{{ route('admin.pages.destroy', $page->slug) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa trang này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm rounded-pill">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center py-4">Chưa có trang tĩnh nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $pages->links() }}</div>
    </div>
</div>
@endsection
