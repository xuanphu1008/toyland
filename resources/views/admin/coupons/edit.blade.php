@extends('layouts.admin')

@section('title', 'Sửa mã giảm giá')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4 mx-auto" style="max-width: 820px;">
    <h5 class="fw-bold text-secondary mb-4">Sửa mã {{ $coupon->code }}</h5>
    <form action="{{ route('admin.coupons.update', $coupon->id) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.coupons._form', ['coupon' => $coupon])
        <div class="d-flex gap-2 pt-3 border-top">
            <button class="btn btn-success rounded-pill px-4">Lưu thay đổi</button>
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy</a>
        </div>
    </form>
</div>
@endsection
