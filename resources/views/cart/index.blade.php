@extends('layouts.user')

@section('title', 'Giỏ hàng')

@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
    <h1 class="tl-section-title"><span>🛒</span> Giỏ hàng của bạn</h1>
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-arrow-left me-1"></i> Tiếp tục mua sắm
    </a>
</div>

@if (count($cart))
@php
$total = 0;
foreach ($cart as $item) { $total += $item['price'] * $item['quantity']; }
$tierDiscount = Auth::user()->tier_discount > 0 ? (int) floor($total * Auth::user()->tier_discount / 100) : 0;
@endphp

<div class="row g-4">
    {{-- ===== Danh sách sản phẩm ===== --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th colspan="2">Sản phẩm</th>
                                <th class="text-end d-none d-md-table-cell">Đơn giá</th>
                                <th class="text-center" style="width: 150px;">Số lượng</th>
                                <th class="text-end">Thành tiền</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cart as $item)
                            @php $lineTotal = $item['price'] * $item['quantity']; @endphp
                            <tr data-product-id="{{ $item['product_id'] }}" data-variant-id="{{ $item['variant_id'] ?? '' }}">
                                <td style="width: 84px;">
                                    <a href="{{ route('products.show', $item['product_id']) }}">
                                        @if (!empty($item['image']))
                                        <img src="{{ \App\Models\Product::imageUrl($item['image']) }}" class="rounded-3"
                                             style="width: 66px; height: 66px; object-fit: cover;" alt="{{ $item['name'] }}">
                                        @else
                                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                                             style="width: 66px; height: 66px; background: var(--tl-primary-100);">
                                            <span style="font-size: 1.6rem">🧸</span>
                                        </div>
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('products.show', $item['product_id']) }}"
                                       class="text-decoration-none text-dark fw-semibold">{{ $item['name'] }}</a>
                                    @if (!empty($item['variant_name']))
                                    <div class="small text-muted">Phân loại: <span class="fw-semibold">{{ $item['variant_name'] }}</span></div>
                                    @endif
                                    <div class="small text-muted d-md-none">{{ number_format($item['price'], 0, ',', '.') }} đ</div>
                                </td>
                                <td class="text-end d-none d-md-table-cell">{{ number_format($item['price'], 0, ',', '.') }} đ</td>
                                <td>
                                    {{-- Dùng lớp giao diện .tl-qty nhưng KHÔNG gắn data-tl-qty:
                                         giỏ hàng có JS riêng để gọi AJAX cập nhật số lượng, nếu gắn thêm
                                         thì bộ xử lý chung trong toyland.js sẽ cộng số lượng lên 2 lần. --}}
                                    <div class="tl-qty tl-qty-sm">
                                        <button type="button" class="tl-qty-btn cart-qty-btn" data-step="-1" aria-label="Giảm">&minus;</button>
                                        <input type="number" class="tl-qty-input cart-qty-input" value="{{ $item['quantity'] }}" min="1" aria-label="Số lượng">
                                        <button type="button" class="tl-qty-btn cart-qty-btn" data-step="1" aria-label="Tăng">+</button>
                                    </div>
                                </td>
                                <td class="text-end fw-bold line-total" style="color: var(--tl-danger)">
                                    {{ number_format($lineTotal, 0, ',', '.') }} đ
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('cart.remove', $item['product_id']) }}" method="POST" class="m-0"
                                          onsubmit="return confirm('Bỏ sản phẩm này khỏi giỏ hàng?')">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="variant_id" value="{{ $item['variant_id'] ?? '' }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle"
                                                style="width:34px;height:34px;padding:0" title="Xóa khỏi giỏ">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-3 mb-0 small">
            <i class="bi bi-info-circle me-1"></i>
            Phí vận chuyển sẽ được tính chính xác ở bước thanh toán, sau khi bạn chọn địa chỉ nhận hàng.
        </div>
    </div>

    {{-- ===== Tóm tắt đơn ===== --}}
    <div class="col-lg-4">
        <div class="card sticky-lg-top" style="top: 90px;">
            <div class="card-body">
                <h2 class="tl-section-title mb-4"><span>🧾</span> Tóm tắt đơn</h2>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tạm tính ({{ count($cart) }} sản phẩm)</span>
                    <span class="fw-semibold" id="cart-grand-total">{{ number_format($total, 0, ',', '.') }} đ</span>
                </div>

                @if ($tierDiscount > 0)
                <div class="d-flex justify-content-between mb-2" style="color: var(--tl-success)">
                    <span>
                        Ưu đãi hạng {{ Auth::user()->tier_label }}
                        <span class="badge bg-success">{{ Auth::user()->tier_discount }}%</span>
                    </span>
                    <span>−{{ number_format($tierDiscount, 0, ',', '.') }} đ</span>
                </div>
                @endif

                <div class="d-flex justify-content-between mb-3 text-muted small">
                    <span>Phí vận chuyển</span>
                    <span>Tính ở bước sau</span>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="fw-bold">Tạm tính</span>
                    <span class="fw-bold fs-4" style="color: var(--tl-danger)">
                        {{ number_format($total - $tierDiscount, 0, ',', '.') }} đ
                    </span>
                </div>

                <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg w-100 mb-2">
                    <i class="bi bi-credit-card me-1"></i> Tiến hành thanh toán
                </a>

                <a href="{{ route('coupons.index') }}" class="btn btn-outline-primary w-100">
                    <i class="bi bi-ticket-perforated me-1"></i> Xem mã giảm giá
                </a>

                <div class="d-flex justify-content-center gap-3 mt-4 small text-muted">
                    <span><i class="bi bi-shield-check"></i> An toàn</span>
                    <span><i class="bi bi-truck"></i> Giao nhanh</span>
                    <span><i class="bi bi-arrow-repeat"></i> Đổi 7 ngày</span>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="tl-empty">
        <span class="tl-empty-icon">🛒</span>
        <h2 class="h4">Giỏ hàng đang trống</h2>
        <p>Hãy chọn vài món đồ chơi mà bé sẽ thích nhé!</p>
        <a href="{{ route('home') }}" class="btn btn-primary btn-lg mt-2">
            <i class="bi bi-bag-heart me-1"></i> Khám phá đồ chơi
        </a>
    </div>
</div>
@endif

@push('scripts')
<script>
    function updateCartQuantity(row, newVal, input) {
        const productId = row.dataset.productId;
        const previousVal = input.value;
        input.disabled = true;

        fetch(`/cart/update/${productId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: new URLSearchParams({ quantity: newVal, variant_id: row.dataset.variantId || '' }),
        })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Có lỗi xảy ra, vui lòng thử lại.');
                return data;
            })
            .then((data) => {
                input.value = data.quantity;
                row.querySelector('.line-total').textContent = data.line_total_formatted;

                const grandTotal = document.getElementById('cart-grand-total');
                if (grandTotal) grandTotal.textContent = data.cart_total_formatted;

                if (typeof updateCartBadge === 'function') updateCartBadge(data.cart_count);

                // Ưu đãi hạng thành viên đổi theo tạm tính -> tải lại để hiển thị đúng con số
                setTimeout(function () { window.location.reload(); }, 400);
            })
            .catch((err) => {
                input.value = previousVal;
                if (typeof showToast === 'function') showToast(err.message, 'danger');
            })
            .finally(() => { input.disabled = false; });
    }

    document.querySelectorAll('.cart-qty-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const row = btn.closest('tr');
            const input = row.querySelector('.cart-qty-input');
            const step = parseInt(btn.dataset.step, 10);
            const newVal = Math.max(1, (parseInt(input.value, 10) || 1) + step);
            updateCartQuantity(row, newVal, input);
        });
    });

    document.querySelectorAll('.cart-qty-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const row = input.closest('tr');
            let newVal = parseInt(input.value, 10);
            if (isNaN(newVal) || newVal < 1) newVal = 1;
            updateCartQuantity(row, newVal, input);
        });
    });
</script>
@endpush
@endsection
