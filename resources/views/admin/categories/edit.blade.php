@extends('layouts.admin')

@section('title', 'Sửa danh mục')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mx-auto" style="max-width: 820px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1">Sửa: {{ $category->name }}</h5>
        <p class="text-muted small mb-4">
            {{ $category->products()->count() }} sản phẩm ·
            {{ $category->children()->count() }} danh mục con
        </p>

        <form action="{{ route('admin.categories.update', $category->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.categories._form', ['category' => $category, 'parents' => $parents])

            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-check-lg me-1"></i> Cập nhật
                </button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>
@endsection
