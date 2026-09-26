@extends(Auth::check() && !Auth::user()->isAdmin() ? 'layouts.user' : 'layout')

@section('title', 'Mã giảm giá')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold" style="color: var(--tl-ink, #4A2F2F);">MÃ GIẢM GIÁ ĐANG CHẠY</h3>
    <p class="text-muted">Sao chép mã và nhập ở bước thanh toán để được giảm giá</p>
</div>

<div class="row g-3">
    @forelse ($coupons as $coupon)
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="text-center bg-danger text-white rounded-4 px-3 py-3" style="min-width: 120px;">
                    <div class="fw-bold fs-5">{{ $coupon->type === 'percent' ? $coupon->value . '%' : number_format($coupon->value, 0, ',', '.') }}</div>
                    <div class="small">{{ $coupon->type === 'percent' ? 'GIẢM GIÁ' : 'VNĐ' }}</div>
                </div>

                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-dark fs-6 font-monospace">{{ $coupon->code }}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill copy-code" data-code="{{ $coupon->code }}">Sao chép</button>
                    </div>
                    <div class="small text-muted">{{ $coupon->description ?: 'Ưu đãi từ ToyLand' }}</div>
                    <div class="small mt-1">
                        Đơn tối thiểu: <strong>{{ number_format($coupon->min_order_amount, 0, ',', '.') }} đ</strong>
                        @if ($coupon->max_discount) · Giảm tối đa <strong>{{ number_format($coupon->max_discount, 0, ',', '.') }} đ</strong> @endif
                    </div>
                    @if ($coupon->expires_at)
                    <div class="small text-danger mt-1">HSD: {{ $coupon->expires_at->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="tl-empty col-12">
        <div class="fs-1 mb-2">🎟️</div>
        Hiện chưa có chương trình khuyến mãi nào đang chạy.
    </div>
    @endforelse
</div>

@push('scripts')
<script>
    document.querySelectorAll('.copy-code').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const code = btn.dataset.code;
            const done = () => { const t = btn.textContent; btn.textContent = 'Đã chép!'; setTimeout(() => btn.textContent = t, 1500); };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(code).then(done);
            } else {
                // Fallback cho http://localhost (clipboard API chỉ chạy trên HTTPS)
                const input = document.createElement('textarea');
                input.value = code;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                done();
            }
        });
    });
</script>
@endpush
@endsection
