@extends('layouts.user')

@section('title', 'Thanh toán đơn hàng')

@section('content')
<h4 class="fw-bold text-secondary mb-4">Thanh toán đơn hàng</h4>

@php
    // Nếu submit trước đó là nhánh "địa chỉ mới" (có nhập ít nhất 1 trong 3 trường new_*),
    // hoặc chưa hề có địa chỉ nào đã lưu, thì phải hiện lại khối "Thêm địa chỉ mới"
    // để người dùng còn thấy được các lỗi validation của new_name/new_phone/new_address.
    $isNewAddressSubmission = old('new_name') !== null || old('new_phone') !== null || old('new_address') !== null;
    $showNewAddressForm = $addresses->isEmpty() || $isNewAddressSubmission;
@endphp

<form action="{{ route('checkout.process') }}" method="POST">
    @csrf
    <div class="row g-4">
        <div class="col-lg-7">
            @if ($errors->any())
            <div class="alert alert-danger rounded-3 border-0 shadow-sm">
                <div class="fw-semibold mb-1">Vui lòng kiểm tra lại thông tin bên dưới:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Thông tin nhận hàng -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-secondary mb-3">Thông tin nhận hàng</h6>

                    @foreach ($addresses as $address)
                    <div class="d-flex align-items-start justify-content-between border rounded-3 p-3 mb-2 tl-address-card">
                        <div class="form-check flex-grow-1 mb-0">
                            <input class="form-check-input address-radio" type="radio" name="address_id"
                                   id="address-{{ $address->id }}" value="{{ $address->id }}"
                                   data-has-location="{{ $address->hasLocation() ? '1' : '0' }}"
                                   data-province-id="{{ $address->province_id }}"
                                   data-district-id="{{ $address->district_id }}"
                                   data-ward-code="{{ $address->ward_code }}"
                                   {{ !$showNewAddressForm && $loop->first ? 'checked' : '' }}>
                            <label class="form-check-label w-100" for="address-{{ $address->id }}">
                                <span class="fw-semibold">{{ $address->name }}</span> - {{ $address->phone }}
                                @if ($address->is_default)
                                <span class="badge bg-secondary ms-1">Mặc định</span>
                                @endif
                                <div class="text-muted small">{{ $address->full_address }}</div>

                                @unless ($address->hasLocation())
                                {{-- Địa chỉ lưu từ trước khi hệ thống có cột Tỉnh/Quận/Phường --}}
                                <div class="small mt-1" style="color: var(--tl-warning);">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    Thiếu Tỉnh/Quận/Phường nên chưa tính được phí ship — bấm “Sửa” để bổ sung.
                                </div>
                                @endunless
                            </label>
                        </div>

                        <div class="d-flex flex-column gap-1 ms-2 flex-shrink-0">
                            {{-- Nút sửa mở modal dùng chung form sổ địa chỉ, sửa xong quay lại đúng trang này --}}
                            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0"
                                    data-bs-toggle="modal" data-bs-target="#edit-address-{{ $address->id }}" title="Sửa địa chỉ">
                                <i class="bi bi-pencil-square"></i> Sửa
                            </button>

                            {{-- Nút này submit tới form độc lập #delete-address-{{ $address->id }} (khai báo sau thẻ </form>
                                 checkout ở cuối trang) qua thuộc tính form="..." - KHÔNG được đặt <form> lồng trong
                                 <form> checkout.process vì HTML không hỗ trợ nested form. --}}
                            <button type="submit" form="delete-address-{{ $address->id }}" class="btn btn-sm btn-link text-danger text-decoration-none p-0"
                                    onclick="return confirm('Bạn có chắc muốn xóa địa chỉ này?');" title="Xóa địa chỉ">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                    @endforeach

                    <div class="form-check border rounded-3 p-3 mb-2">
                        <input class="form-check-input address-radio" type="radio" name="address_id" id="address-new" value=""
                               data-has-location="0" {{ $showNewAddressForm ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="address-new">+ Thêm địa chỉ mới</label>
                    </div>

                    <div id="new-address-fields" class="border rounded-3 p-3 {{ $showNewAddressForm ? '' : 'd-none' }}">
                        <div class="mb-2">
                            <label class="form-label small">Họ tên người nhận</label>
                            <input type="text" name="new_name" class="form-control @error('new_name') is-invalid @enderror" value="{{ old('new_name') }}">
                            @error('new_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Số điện thoại</label>
                            <input type="text" name="new_phone" class="form-control @error('new_phone') is-invalid @enderror" value="{{ old('new_phone') }}">
                            @error('new_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Địa chỉ chi tiết (số nhà, tên đường)</label>
                            <textarea name="new_address" class="form-control @error('new_address') is-invalid @enderror" rows="2">{{ old('new_address') }}</textarea>
                            @error('new_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Khu vực giao hàng -->
            {{-- Khối này nằm NGOÀI "Thêm địa chỉ mới". Trước đây nó bị nhét bên trong,
                 nên chọn địa chỉ đã lưu là khối bị ẩn theo và không còn chỗ nào chọn
                 khu vực => phí vận chuyển luôn bằng 0 / không tính được. --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4" id="shipping-area-card">
                <div class="card-body">
                    <h6 class="fw-bold text-secondary mb-3">
                        <i class="bi bi-geo-alt me-1"></i> Khu vực giao hàng
                    </h6>

                    {{-- Khi địa chỉ đã lưu có sẵn Tỉnh/Quận/Phường thì chỉ hiển thị lại cho gọn --}}
                    <div id="saved-location-summary" class="alert alert-success border-0 rounded-3 d-none mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Giao tới: <strong id="saved-location-text"></strong>
                        <div class="small mt-1">Muốn đổi khu vực, hãy bấm “Sửa” ở địa chỉ tương ứng phía trên.</div>
                    </div>

                    <div id="manual-location-picker">
                        @include('partials._location-picker', [
                            'codePrefix' => 'to_',
                            'namePrefix' => 'new_',
                            'idSuffix' => 'checkout',
                        ])
                    </div>

                    <div id="ghn-shipping-hint" class="form-text mt-2"></div>
                    <input type="hidden" name="ghn_total_fee" id="ghn_total_fee" value="0">
                </div>
            </div>

            <!-- Phương thức thanh toán -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h6 class="fw-bold text-secondary mb-3">Phương thức thanh toán</h6>

                    <div class="form-check border rounded-3 p-3 mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="payment-cod" value="cod"
                            {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}>
                        <label class="form-check-label" for="payment-cod">
                            <i class="bi bi-cash-coin"></i> Thanh toán khi nhận hàng (COD)
                        </label>
                    </div>
                    <div class="border rounded-3 p-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="payment-momo" value="momo"
                                {{ old('payment_method') === 'momo' ? 'checked' : '' }}>
                            <label class="form-check-label" for="payment-momo">
                                <i class="bi bi-wallet2"></i> Thanh toán online qua MoMo
                            </label>
                        </div>

                        {{-- Các phương thức con của cổng MoMo: ví/QR, thẻ ATM nội địa, thẻ quốc tế...
                             Chỉ hiện khi khách chọn MoMo (JS bên dưới bật/tắt khối này). --}}
                        <div id="momo-options" class="mt-3 ps-4 d-none">
                            <div class="small text-muted mb-2">Chọn hình thức thanh toán MoMo:</div>

                            @foreach ($momoOptions as $optionKey => $option)
                            <div class="form-check mb-2">
                                <input class="form-check-input momo-option-radio" type="radio" name="momo_payment_option"
                                    id="momo-option-{{ $optionKey }}" value="{{ $optionKey }}"
                                    {{ old('momo_payment_option', $defaultMomoOption) === $optionKey ? 'checked' : '' }}>
                                <label class="form-check-label" for="momo-option-{{ $optionKey }}">
                                    <span class="fw-semibold"><i class="bi {{ $option['icon'] }}"></i> {{ $option['label'] }}</span>
                                    <span class="d-block small text-muted">{{ $option['description'] }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    @error('momo_payment_option')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Tóm tắt đơn hàng -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h6 class="fw-bold text-secondary mb-3">Đơn hàng của bạn</h6>

                    @foreach ($cart as $item)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="small">
                            <span class="fw-semibold">{{ $item['name'] }}</span>
                            <span class="text-muted"> x{{ $item['quantity'] }}</span>
                        </div>
                        <span class="text-danger small fw-semibold">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }} đ</span>
                    </div>
                    @endforeach

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="text-muted">Tạm tính</span>
                        <span>{{ number_format($subtotal, 0, ',', '.') }} đ</span>
                    </div>

                    @if ($tierDiscount > 0)
                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="text-success">
                            Ưu đãi hạng {{ $user->tier_label }} ({{ $user->tier_discount }}%)
                        </span>
                        <span class="text-success">-{{ number_format($tierDiscount, 0, ',', '.') }} đ</span>
                    </div>
                    @endif

                    {{-- ===== MÃ GIẢM GIÁ =====
                         Input/nút dùng thuộc tính form="..." để trỏ tới form riêng khai báo
                         sau thẻ </form> checkout, vì HTML không cho phép lồng form trong form. --}}
                    @if ($coupon)
                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="text-success">
                            Mã <span class="badge bg-success font-monospace">{{ $coupon->code }}</span>
                            <button type="submit" form="coupon-remove-form" class="btn btn-link btn-sm text-danger p-0 ms-1 align-baseline">gỡ</button>
                        </span>
                        <span class="text-success">-{{ number_format($couponDiscount, 0, ',', '.') }} đ</span>
                    </div>
                    @else
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Mã giảm giá</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="code" form="coupon-apply-form" class="form-control text-uppercase" maxlength="50" placeholder="Nhập mã giảm giá">
                            <button type="submit" form="coupon-apply-form" class="btn btn-outline-primary">Áp dụng</button>
                        </div>
                        <div class="form-text">
                            <a href="{{ route('coupons.index') }}" target="_blank">Xem mã đang chạy</a>
                        </div>
                    </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                        <span>Phí vận chuyển</span>
                        <span id="shipping-fee-display">Chưa tính (vui lòng chọn Tỉnh/Quận/Phường)</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Tổng cộng</span>
                        <span class="fw-bold text-danger fs-5" id="grand-total-display" data-subtotal="{{ (int) $total }}">{{ number_format($total, 0, ',', '.') }} đ</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill mt-4">Xác nhận Đặt hàng</button>
                    <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary w-100 rounded-pill mt-2">Quay lại giỏ hàng</a>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Form độc lập cho thao tác mã giảm giá (không được lồng trong form checkout) --}}
<form id="coupon-apply-form" action="{{ route('coupons.apply') }}" method="POST" class="d-none">
    @csrf
</form>
<form id="coupon-remove-form" action="{{ route('coupons.remove') }}" method="POST" class="d-none">
    @csrf @method('DELETE')
</form>

{{-- Các form Xóa địa chỉ được đặt ĐỘC LẬP, nằm ngoài form checkout ở trên (tránh lỗi nested <form>),
     và được liên kết với nút Xóa tương ứng ở trên qua thuộc tính form="delete-address-{id}". --}}
@foreach ($addresses as $address)
<form id="delete-address-{{ $address->id }}" action="{{ route('checkout.addresses.destroy', $address->id) }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endforeach

@push('scripts')
@include('partials._location-scripts')
<script>
    // ----- Hiện/ẩn danh sách phương thức MoMo theo phương thức thanh toán được chọn -----
    (function () {
        const momoOptions = document.getElementById('momo-options');
        const methodRadios = document.querySelectorAll('input[name="payment_method"]');
        if (!momoOptions || !methodRadios.length) return;

        function syncMomoOptions() {
            const momoRadio = document.getElementById('payment-momo');
            const isMomo = momoRadio && momoRadio.checked;

            momoOptions.classList.toggle('d-none', !isMomo);

            // Không chọn MoMo thì vô hiệu hóa các radio con để chúng không bị gửi lên server
            momoOptions.querySelectorAll('.momo-option-radio').forEach(function (radio) {
                radio.disabled = !isMomo;
            });
        }

        methodRadios.forEach(function (radio) {
            radio.addEventListener('change', syncMomoOptions);
        });

        syncMomoOptions();
    })();

    // ----- Chọn địa chỉ: hiện/ẩn bộ chọn khu vực & tính lại phí vận chuyển -----
    (function () {
        const feeDisplay = document.getElementById('shipping-fee-display');
        const grandTotalDisplay = document.getElementById('grand-total-display');
        const ghnTotalFeeInput = document.getElementById('ghn_total_fee');
        const hint = document.getElementById('ghn-shipping-hint');
        const summaryBox = document.getElementById('saved-location-summary');
        const summaryText = document.getElementById('saved-location-text');
        const manualBox = document.getElementById('manual-location-picker');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const subtotal = parseInt(grandTotalDisplay.dataset.subtotal, 10) || 0;

        const districtInput = manualBox.querySelector('[data-tl-district-id]');
        const wardInput = manualBox.querySelector('[data-tl-ward-code]');

        const formatCurrency = (n) => new Intl.NumberFormat('vi-VN').format(n) + ' đ';

        function updateGrandTotal(fee) {
            grandTotalDisplay.textContent = formatCurrency(subtotal + fee);
        }

        function resetFee(message) {
            feeDisplay.textContent = message;
            ghnTotalFeeInput.value = '0';
            updateGrandTotal(0);
        }

        // Gọi API tính phí. districtId/wardCode có thể đến từ bộ chọn thủ công
        // HOẶC từ địa chỉ đã lưu - cả hai đường đều dùng chung hàm này.
        function calculateFee(districtId, wardCode) {
            if (!districtId || !wardCode) {
                resetFee('Chưa tính (vui lòng chọn Tỉnh/Quận/Phường)');
                return;
            }

            feeDisplay.textContent = 'Đang tính phí...';
            if (hint) hint.textContent = '';

            fetch("{{ route('locations.fee') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ to_district_id: districtId, to_ward_code: wardCode }),
            })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Không tính được phí vận chuyển.');
                    return data;
                })
                .then((data) => {
                    feeDisplay.textContent = formatCurrency(data.total_fee);
                    ghnTotalFeeInput.value = data.total_fee;
                    updateGrandTotal(data.total_fee);
                })
                .catch((err) => {
                    resetFee('Không tính được phí vận chuyển');
                    if (hint) hint.textContent = err.message;
                });
        }

        // Bộ chọn dùng chung bắn sự kiện này mỗi khi khách đổi Tỉnh/Quận/Phường
        document.addEventListener('tl:location-change', function (event) {
            // Bỏ qua bộ chọn nằm trong modal "Sửa địa chỉ" - nó phục vụ form khác,
            // không phải khu vực giao của đơn đang đặt.
            if (event.target.closest('.modal')) return;

            const selected = document.querySelector('.address-radio:checked');
            if (selected && selected.dataset.hasLocation === '1') return; // đang dùng địa chỉ đã lưu

            calculateFee(event.detail.districtId, event.detail.wardCode);
        });

        // Đồng bộ toàn bộ khối "Khu vực giao hàng" theo địa chỉ đang chọn
        function syncAddressSelection() {
            const selected = document.querySelector('.address-radio:checked');
            const newFields = document.getElementById('new-address-fields');

            newFields.classList.toggle('d-none', !selected || selected.id !== 'address-new');

            if (selected && selected.dataset.hasLocation === '1') {
                // Địa chỉ đã lưu đủ Tỉnh/Quận/Phường -> khỏi bắt khách chọn lại
                const label = selected.closest('.tl-address-card').querySelector('.text-muted.small');
                summaryText.textContent = label ? label.textContent.trim() : '';
                summaryBox.classList.remove('d-none');
                manualBox.classList.add('d-none');

                // Ghi vào input ẩn để server nhận được cùng bộ mã
                districtInput.value = selected.dataset.districtId || '';
                wardInput.value = selected.dataset.wardCode || '';

                calculateFee(selected.dataset.districtId, selected.dataset.wardCode);
                return;
            }

            // Địa chỉ mới, hoặc địa chỉ cũ chưa có mã hành chính -> hiện bộ chọn
            summaryBox.classList.add('d-none');
            manualBox.classList.remove('d-none');
            calculateFee(districtInput.value, wardInput.value);
        }

        document.querySelectorAll('.address-radio').forEach(function (radio) {
            radio.addEventListener('change', syncAddressSelection);
        });

        syncAddressSelection();
    })();

</script>
@endpush

@push('modals')
{{-- Modal SỬA địa chỉ.
     Đẩy vào stack 'modals' để Blade render nó ở sát </body>, thành con trực tiếp của body.
     Nếu để trong @section('content') thì modal nằm trong <main> (đã có z-index nên tạo
     stacking context), còn lớp phủ .modal-backdrop lại gắn thẳng vào body với z-index 1050
     -> lớp phủ đè lên chính modal, màn hình tối đen và không thao tác được.

     Modal cũng phải nằm NGOÀI <form> checkout vì bên trong nó đã có <form> riêng
     (HTML không cho phép form lồng form).

     Form gửi tới profile.addresses.update kèm redirect_to=checkout để sửa xong
     quay lại đúng trang thanh toán. --}}
@foreach ($addresses as $address)
<div class="modal fade" id="edit-address-{{ $address->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="{{ route('profile.addresses.update', $address->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_to" value="checkout">

                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1"></i> Sửa địa chỉ nhận hàng</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">Tên người nhận <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $address->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ $address->phone }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Địa chỉ chi tiết (số nhà, tên đường) <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="2" required>{{ $address->address }}</textarea>
                    </div>

                    @include('partials._location-picker', [
                        'codePrefix' => '',
                        'namePrefix' => '',
                        'address' => $address,
                        'idSuffix' => 'edit-' . $address->id,
                    ])
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Lưu địa chỉ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endpush

@endsection
