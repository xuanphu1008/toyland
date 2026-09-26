@extends('layouts.admin')

@section('title', 'Viết bài mới')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4 mx-auto" style="max-width: 900px;">
    <h5 class="fw-bold text-secondary mb-4">Viết bài mới</h5>
    <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.posts._form', ['post' => null])
        <div class="d-flex gap-2 pt-3 mt-3 border-top">
            <button class="btn btn-primary rounded-pill px-4">Đăng bài</button>
            <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy</a>
        </div>
    </form>
</div>
@endsection
