@extends('layouts.admin')

@section('title', 'Sửa bài viết')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4 mx-auto" style="max-width: 900px;">
    <h5 class="fw-bold text-secondary mb-4">Sửa bài: {{ $post->title }}</h5>
    <form action="{{ route('admin.posts.update', $post->slug) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.posts._form', ['post' => $post])
        <div class="d-flex gap-2 pt-3 mt-3 border-top">
            <button class="btn btn-success rounded-pill px-4">Lưu thay đổi</button>
            <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy</a>
        </div>
    </form>
</div>
@endsection
