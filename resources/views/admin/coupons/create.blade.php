@extends('layouts.admin')

@section('title', 'Tạo mã giảm giá')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4 mx-auto" style="max-width: 820px;">
    <h5 class="fw-bold text-secondary mb-4">Tạo mã giảm giá mới</h5>
    <form action="{{ route('admin.coupons.store') }}" method="POST">
        @csrf
        @include('admin.coupons._form', ['coupon' => null])
        <div class="d-flex gap-2 pt-3 border-top">
            <button class="btn btn-primary rounded-pill px-4">Tạo mã</button>
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy</a>
        </div>
    </form>
</div>
@endsection
