@extends('layouts.admin')

@section('title', 'Sửa câu hỏi')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4 mx-auto" style="max-width: 820px;">
    <h5 class="fw-bold text-secondary mb-4">Sửa câu hỏi thường gặp</h5>
    <form action="{{ route('admin.faqs.update', $faq->id) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.faqs._form', ['faq' => $faq])
        <div class="d-flex gap-2 pt-3 mt-3 border-top">
            <button class="btn btn-success rounded-pill px-4">Lưu thay đổi</button>
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy</a>
        </div>
    </form>
</div>
@endsection
