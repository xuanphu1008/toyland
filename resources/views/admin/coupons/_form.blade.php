@if ($errors->any())
<div class="alert alert-danger rounded-3 border-0">
    <ul class="mb-0 small">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Mã giảm giá <span class="text-danger">*</span></label>
        <input type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" class="form-control text-uppercase font-monospace" required placeholder="TOYLAND10">
        <div class="form-text">Chỉ gồm chữ cái, số, dấu gạch ngang và gạch dưới.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Mô tả ngắn</label>
        <input type="text" name="description" value="{{ old('description', $coupon->description ?? '') }}" class="form-control" maxlength="255" placeholder="Giảm 10% cho đơn từ 500k">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Loại giảm giá <span class="text-danger">*</span></label>
        <select name="type" class="form-select" required>
            @foreach (\App\Models\Coupon::TYPES as $value => $label)
            <option value="{{ $value }}" {{ old('type', $coupon->type ?? 'percent') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Giá trị giảm <span class="text-danger">*</span></label>
        <input type="number" name="value" value="{{ old('value', $coupon->value ?? 10) }}" min="1" class="form-control" required>
        <div class="form-text">Nhập số % (tối đa 100) hoặc số tiền VNĐ tùy loại đã chọn.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Giảm tối đa (VNĐ)</label>
        <input type="number" name="max_discount" value="{{ old('max_discount', $coupon->max_discount ?? '') }}" min="0" class="form-control" placeholder="Bỏ trống = không giới hạn">
        <div class="form-text">Chỉ áp dụng cho loại giảm theo %.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Đơn tối thiểu (VNĐ)</label>
        <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount ?? 0) }}" min="0" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Tổng lượt dùng</label>
        <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}" min="1" class="form-control" placeholder="Bỏ trống = không giới hạn">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Số lượt mỗi khách <span class="text-danger">*</span></label>
        <input type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? 1) }}" min="1" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Bắt đầu từ</label>
        <input type="datetime-local" name="starts_at" class="form-control"
               value="{{ old('starts_at', isset($coupon->starts_at) ? $coupon->starts_at->format('Y-m-d\TH:i') : '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Hết hạn lúc</label>
        <input type="datetime-local" name="expires_at" class="form-control"
               value="{{ old('expires_at', isset($coupon->expires_at) ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}">
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                   {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="isActive">Kích hoạt mã này</label>
        </div>
    </div>
</div>
