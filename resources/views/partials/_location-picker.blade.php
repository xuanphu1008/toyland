{{--
    Bộ chọn Tỉnh / Quận / Phường dùng chung (dữ liệu lấy từ API GHN).

    Tham số:
      $codePrefix - tiền tố cho các input ẩn chứa MÃ. Trang checkout dùng 'to_'
                    (to_district_id, to_ward_code) vì controller đang đọc tên đó;
                    form sổ địa chỉ dùng '' (province_id, district_id, ward_code).
      $namePrefix - tiền tố cho các input ẩn chứa TÊN hành chính.
                    Checkout dùng 'new_', form sổ địa chỉ dùng ''.
      $address    - model UserAddress khi đang SỬA, để chọn sẵn khu vực cũ.
      $idSuffix   - hậu tố id, tránh trùng khi có nhiều bộ chọn trên cùng một trang.
--}}
@php
    $codePrefix = $codePrefix ?? '';
    $namePrefix = $namePrefix ?? '';
    $address = $address ?? null;
    $idSuffix = $idSuffix ?? 'main';
@endphp

<div class="row g-2"
     data-tl-location
     data-preset-province="{{ $address->province_id ?? '' }}"
     data-preset-district="{{ $address->district_id ?? '' }}"
     data-preset-ward="{{ $address->ward_code ?? '' }}">

    <div class="col-md-4">
        <label class="form-label small" for="prov-{{ $idSuffix }}">Tỉnh/Thành phố <span class="text-danger">*</span></label>
        <select class="form-select" id="prov-{{ $idSuffix }}" data-tl-province>
            <option value="">-- Chọn Tỉnh/Thành --</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label small" for="dist-{{ $idSuffix }}">Quận/Huyện <span class="text-danger">*</span></label>
        <select class="form-select" id="dist-{{ $idSuffix }}" data-tl-district disabled>
            <option value="">-- Chọn Quận/Huyện --</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label small" for="ward-{{ $idSuffix }}">Phường/Xã <span class="text-danger">*</span></label>
        <select class="form-select" id="ward-{{ $idSuffix }}" data-tl-ward disabled>
            <option value="">-- Chọn Phường/Xã --</option>
        </select>
    </div>

    <div class="col-12">
        <div class="form-text" data-tl-location-status></div>
    </div>

    {{-- Mã hành chính: dùng để tính phí GHN và lưu vào sổ địa chỉ --}}
    <input type="hidden" name="{{ $codePrefix }}province_id" data-tl-province-id value="{{ $address->province_id ?? '' }}">
    <input type="hidden" name="{{ $codePrefix }}district_id" data-tl-district-id value="{{ $address->district_id ?? '' }}">
    <input type="hidden" name="{{ $codePrefix }}ward_code" data-tl-ward-code value="{{ $address->ward_code ?? '' }}">

    {{-- Tên hành chính: để hiển thị lại cho khách đọc --}}
    <input type="hidden" name="{{ $namePrefix }}province_name" data-tl-province-name value="{{ $address->province_name ?? '' }}">
    <input type="hidden" name="{{ $namePrefix }}district_name" data-tl-district-name value="{{ $address->district_name ?? '' }}">
    <input type="hidden" name="{{ $namePrefix }}ward_name" data-tl-ward-name value="{{ $address->ward_name ?? '' }}">
</div>
