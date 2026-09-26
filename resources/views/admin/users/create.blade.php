@extends('layouts.admin')

@section('title', 'Thêm người dùng')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mx-auto" style="max-width: 820px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Thêm Người Dùng</h5>

        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            @include('admin.users._form', ['user' => null])

            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-check-lg me-1"></i> Tạo tài khoản
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>
@endsection
