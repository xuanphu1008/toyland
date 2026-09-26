@extends('layouts.admin')

@section('title', 'Thêm câu hỏi')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4 mx-auto" style="max-width: 820px;">
    <h5 class="fw-bold text-secondary mb-4">Thêm câu hỏi thường gặp</h5>
    <form action="{{ route('admin.faqs.store') }}" method="POST">
        @csrf
        @include('admin.faqs._form', ['faq' => null])
        <div class="d-flex gap-2 pt-3 mt-3 border-top">
            <button class="btn btn-primary rounded-pill px-4">Lưu câu hỏi</button>
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy</a>
        </div>
    </form>
</div>
@endsection
