@extends('layouts.user')

@section('title', 'Thanh toán MoMo')

@section('content')
<div class="d-flex flex-column align-items-center justify-content-center text-center py-5">
    <div class="spinner-border text-danger mb-4" style="width: 3rem; height: 3rem;" role="status"></div>
    <h4 class="fw-bold text-secondary">Đang chuyển hướng sang cổng thanh toán MoMo...</h4>
    <p class="text-muted mb-0">Đơn hàng #{{ $order->id }} - {{ number_format($order->total_price, 0, ',', '.') }} đ</p>
    <p class="text-muted small">(Luồng mô phỏng MoMo Sandbox - tích hợp API thanh toán thật sẽ được bổ sung sau)</p>
    <a href="{{ route('orders.index') }}" class="btn btn-outline-primary rounded-pill px-4 mt-3">Xem lịch sử đơn hàng</a>
</div>

@push('scripts')
<script>
    setTimeout(function () {
        window.location.href = "{{ route('orders.index') }}";
    }, 3000);
</script>
@endpush
@endsection
