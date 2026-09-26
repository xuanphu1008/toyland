@extends('layouts.admin')

@section('title', 'Sửa người dùng')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mx-auto" style="max-width: 820px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1">Sửa: {{ $user->name }}</h5>
        <p class="text-muted small mb-4">Tham gia ngày {{ optional($user->created_at)->format('d/m/Y') }}</p>

        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.users._form', ['user' => $user])

            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-check-lg me-1"></i> Cập nhật
                </button>
                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-outline-primary rounded-pill px-4">Xem chi tiết</a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>
@endsection
