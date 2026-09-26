@extends('layouts.admin')

@section('title', 'Câu hỏi thường gặp')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-secondary fw-bold mb-0">Câu Hỏi Thường Gặp (FAQ)</h5>
    <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">+ Thêm câu hỏi</a>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <form action="{{ route('admin.faqs.index') }}" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-6"><input type="text" name="search" class="form-control" placeholder="Tìm câu hỏi..." value="{{ request('search') }}"></div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">-- Tất cả nhóm --</option>
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
                        <th style="width:70px;">Thứ tự</th>
                        <th>Nhóm</th>
                        <th>Câu hỏi</th>
                        <th class="text-center">Hiển thị</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faqs as $faq)
                    <tr>
                        <td class="text-muted">{{ $faq->sort_order }}</td>
                        <td><span class="badge bg-info text-dark">{{ $faq->category }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $faq->question }}</div>
                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($faq->answer, 110) }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $faq->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $faq->is_active ? 'Đang hiện' : 'Đang ẩn' }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.faqs.edit', $faq->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill">Sửa</a>
                            <form action="{{ route('admin.faqs.destroy', $faq->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa câu hỏi này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm rounded-pill">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-muted text-center py-4">Chưa có câu hỏi nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">{{ $faqs->links() }}</div>
    </div>
</div>
@endsection
