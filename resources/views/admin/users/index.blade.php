@extends('layouts.admin')

@section('title', 'Quản lý Người dùng')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="text-secondary fw-bold mb-0">Quản Lý Người Dùng</h5>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                + Thêm Mới
            </a>
        </div>

        <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Tìm tên hoặc email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="role" class="form-select">
                    <option value="">-- Tất cả vai trò --</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Quản trị viên</option>
                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Khách hàng</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100">Lọc & Tìm kiếm</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th class="text-start">Họ tên</th>
                        <th class="text-start">Email</th>
                        <th>Vai trò</th>
                        <th>Số đơn hàng</th>
                        <th>Xác thực</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                    <tr>
                        <td>#{{ $user->id }}</td>
                        <td class="text-start fw-bold text-dark">{{ $user->name }}</td>
                        <td class="text-start">{{ $user->email }}</td>
                        <td>
                            @if ($user->isAdmin())
                            <span class="badge bg-primary">Quản trị viên</span>
                            @else
                            <span class="badge bg-secondary">Khách hàng</span>
                            @endif
                        </td>
                        <td>{{ $user->orders_count }}</td>
                        <td>
                            @if ($user->email_verified_at)
                            <span class="badge bg-success">Đã xác thực</span>
                            @else
                            <span class="badge bg-warning text-dark">Chưa xác thực</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('d/m/Y') }}</td>
                        <td>
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline">
                                <a class="btn btn-outline-info btn-sm rounded-pill" href="{{ route('admin.users.show', $user->id) }}">Xem</a>
                                <a class="btn btn-outline-secondary btn-sm rounded-pill mx-1" href="{{ route('admin.users.edit', $user->id) }}">Sửa</a>
                                @csrf @method('DELETE')
                                @if ($user->id !== auth()->id())
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill" onclick="return confirm('Bạn có chắc muốn xóa người dùng này? Toàn bộ đơn hàng liên quan cũng sẽ bị xóa.')">Xóa</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-muted py-4">Không tìm thấy người dùng nào!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
