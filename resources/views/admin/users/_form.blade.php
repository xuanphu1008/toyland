{{-- Form dùng chung Thêm / Sửa người dùng. Biến: $user (null khi thêm mới) --}}
@php $user = $user ?? null; @endphp

@if ($errors->any())
<div class="alert alert-danger rounded-3 border-0">
    <ul class="mb-0 small">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
               class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">
            Mật khẩu @if (! $user)<span class="text-danger">*</span>@endif
        </label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
               minlength="6" {{ $user ? '' : 'required' }}>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if ($user)
        <div class="form-text">Để trống nếu không muốn đổi mật khẩu.</div>
        @endif
    </div>

    <div class="col-md-6">
        <label class="form-label">Nhập lại mật khẩu</label>
        <input type="password" name="password_confirmation" class="form-control" minlength="6" {{ $user ? '' : 'required' }}>
    </div>

    <div class="col-md-6">
        <label class="form-label">Vai trò <span class="text-danger">*</span></label>
        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
            <option value="user" {{ old('role', $user->role ?? 'user') === 'user' ? 'selected' : '' }}>Khách hàng</option>
            <option value="admin" {{ old('role', $user->role ?? '') === 'admin' ? 'selected' : '' }}>Quản trị viên</option>
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Quản trị viên có toàn quyền trên trang quản trị — chỉ cấp khi thật sự cần.</div>
    </div>
</div>
