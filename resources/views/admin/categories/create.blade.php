@extends('layouts.admin')

@section('title', 'Thêm danh mục')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mx-auto" style="max-width: 820px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Thêm Danh Mục</h5>

        <form action="{{ route('admin.categories.store') }}" method="POST">
            @csrf
            @include('admin.categories._form', ['category' => null, 'parents' => $parents])

            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-check-lg me-1"></i> Lưu danh mục
                </button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>
@endsection
