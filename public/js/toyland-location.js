/*!
 * ToyLand - Bộ chọn Tỉnh / Quận / Phường (dữ liệu GHN)
 *
 * Trước đây đoạn logic này nằm thẳng trong checkout/index.blade.php nên chỉ
 * dùng được ở đúng một chỗ, và nó nằm BÊN TRONG khối "thêm địa chỉ mới" -
 * chọn địa chỉ đã lưu là khối đó bị ẩn, kéo theo không còn nơi nào để chọn
 * khu vực giao hàng => không tính được phí ship.
 *
 * Tách ra thành module dùng chung: mỗi khối <div data-tl-location> trên trang
 * (form thêm địa chỉ, form sửa địa chỉ, khu vực giao hàng ở checkout...) tự
 * khởi tạo độc lập.
 *
 * Cách dùng trong Blade: xem resources/views/partials/_location-picker.blade.php
 */
(function () {
    'use strict';

    var ENDPOINTS = window.TL_LOCATION_ENDPOINTS || {};

    function el(root, selector) {
        return root.querySelector(selector);
    }

    function resetSelect(select, placeholder) {
        if (!select) return;
        select.innerHTML = '<option value="">' + placeholder + '</option>';
        select.disabled = true;
    }

    function fillOptions(select, rows, valueKey, labelKey, selectedValue) {
        if (!select) return;
        var html = '<option value="">-- Chọn --</option>';

        (rows || []).forEach(function (row) {
            var value = row[valueKey];
            var label = row[labelKey];
            var selected = String(value) === String(selectedValue || '') ? ' selected' : '';
            html += '<option value="' + value + '"' + selected + '>' + label + '</option>';
        });

        select.innerHTML = html;
        select.disabled = false;
    }

    function getJson(url) {
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.json(); });
    }

    function LocationPicker(root) {
        this.root = root;

        this.province = el(root, '[data-tl-province]');
        this.district = el(root, '[data-tl-district]');
        this.ward = el(root, '[data-tl-ward]');

        this.provinceId = el(root, '[data-tl-province-id]');
        this.districtId = el(root, '[data-tl-district-id]');
        this.wardCode = el(root, '[data-tl-ward-code]');

        this.provinceName = el(root, '[data-tl-province-name]');
        this.districtName = el(root, '[data-tl-district-name]');
        this.wardName = el(root, '[data-tl-ward-name]');

        this.status = el(root, '[data-tl-location-status]');

        // Giá trị có sẵn khi SỬA một địa chỉ đã lưu
        this.preset = {
            province: root.dataset.presetProvince || '',
            district: root.dataset.presetDistrict || '',
            ward: root.dataset.presetWard || ''
        };

        this.bind();
        this.loadProvinces();
    }

    LocationPicker.prototype.say = function (message, tone) {
        if (!this.status) return;
        this.status.className = 'form-text' + (tone ? ' text-' + tone : '');
        this.status.textContent = message || '';
    };

    LocationPicker.prototype.setHidden = function (input, value) {
        if (input) input.value = value || '';
    };

    LocationPicker.prototype.selectedLabel = function (select) {
        if (!select || select.selectedIndex < 0) return '';
        var option = select.options[select.selectedIndex];
        return option && option.value ? option.textContent.trim() : '';
    };

    LocationPicker.prototype.bind = function () {
        var self = this;

        this.province && this.province.addEventListener('change', function () {
            self.setHidden(self.provinceId, this.value);
            self.setHidden(self.provinceName, self.selectedLabel(this));
            self.clearFrom('district');
            if (this.value) self.loadDistricts(this.value);
        });

        this.district && this.district.addEventListener('change', function () {
            self.setHidden(self.districtId, this.value);
            self.setHidden(self.districtName, self.selectedLabel(this));
            self.clearFrom('ward');
            if (this.value) self.loadWards(this.value);
        });

        this.ward && this.ward.addEventListener('change', function () {
            self.setHidden(self.wardCode, this.value);
            self.setHidden(self.wardName, self.selectedLabel(this));
            self.notifyChange();
        });
    };

    // Xóa các cấp phía sau khi cấp trên đổi
    LocationPicker.prototype.clearFrom = function (level) {
        if (level === 'district') {
            resetSelect(this.district, '-- Chọn Quận/Huyện --');
            this.setHidden(this.districtId, '');
            this.setHidden(this.districtName, '');
        }

        resetSelect(this.ward, '-- Chọn Phường/Xã --');
        this.setHidden(this.wardCode, '');
        this.setHidden(this.wardName, '');
        this.notifyChange();
    };

    // Báo ra ngoài để trang checkout gọi API tính phí
    LocationPicker.prototype.notifyChange = function () {
        this.root.dispatchEvent(new CustomEvent('tl:location-change', {
            bubbles: true,
            detail: {
                provinceId: this.provinceId ? this.provinceId.value : '',
                districtId: this.districtId ? this.districtId.value : '',
                wardCode: this.wardCode ? this.wardCode.value : ''
            }
        }));
    };

    LocationPicker.prototype.loadProvinces = function () {
        var self = this;

        if (!ENDPOINTS.provinces || !this.province) return;

        this.say('Đang tải danh sách tỉnh/thành...');

        getJson(ENDPOINTS.provinces)
            .then(function (data) {
                if (!data || data.success === false) throw new Error('Không tải được danh sách tỉnh/thành');

                fillOptions(self.province, data.data, 'ProvinceID', 'ProvinceName', self.preset.province);
                self.say('');

                if (self.preset.province) {
                    self.setHidden(self.provinceId, self.preset.province);
                    self.setHidden(self.provinceName, self.selectedLabel(self.province));
                    self.loadDistricts(self.preset.province);
                }
            })
            .catch(function () {
                self.say('Không tải được danh sách tỉnh/thành. Kiểm tra GHN_TOKEN trong file .env.', 'danger');
            });
    };

    LocationPicker.prototype.loadDistricts = function (provinceId) {
        var self = this;

        if (!ENDPOINTS.districts || !this.district) return;

        this.say('Đang tải quận/huyện...');

        getJson(ENDPOINTS.districts + '?province_id=' + encodeURIComponent(provinceId))
            .then(function (data) {
                if (!data || data.success === false) throw new Error('fail');

                fillOptions(self.district, data.data, 'DistrictID', 'DistrictName', self.preset.district);
                self.say('');

                if (self.preset.district) {
                    self.setHidden(self.districtId, self.preset.district);
                    self.setHidden(self.districtName, self.selectedLabel(self.district));
                    self.loadWards(self.preset.district);
                }
            })
            .catch(function () {
                self.say('Không tải được quận/huyện.', 'danger');
            });
    };

    LocationPicker.prototype.loadWards = function (districtId) {
        var self = this;

        if (!ENDPOINTS.wards || !this.ward) return;

        this.say('Đang tải phường/xã...');

        getJson(ENDPOINTS.wards + '?district_id=' + encodeURIComponent(districtId))
            .then(function (data) {
                if (!data || data.success === false) throw new Error('fail');

                fillOptions(self.ward, data.data, 'WardCode', 'WardName', self.preset.ward);
                self.say('');

                if (self.preset.ward) {
                    self.setHidden(self.wardCode, self.preset.ward);
                    self.setHidden(self.wardName, self.selectedLabel(self.ward));
                }

                // Preset chỉ dùng cho lần nạp đầu tiên, sau đó người dùng tự chọn
                self.preset = { province: '', district: '', ward: '' };
                self.notifyChange();
            })
            .catch(function () {
                self.say('Không tải được phường/xã.', 'danger');
            });
    };

    function initAll(scope, force) {
        (scope || document).querySelectorAll('[data-tl-location]').forEach(function (root) {
            if (root.dataset.tlLocationReady === '1') return;

            // Bộ chọn nằm trong modal đang đóng thì chưa khởi tạo vội:
            // mỗi lần khởi tạo là một lượt gọi API tỉnh/thành, không nên gọi
            // cho cả chục địa chỉ ngay khi mở trang.
            if (!force && root.offsetParent === null) return;

            root.dataset.tlLocationReady = '1';
            new LocationPicker(root);
        });
    }

    // Modal mở ra mới khởi tạo bộ chọn bên trong nó
    document.addEventListener('shown.bs.modal', function (event) {
        initAll(event.target, true);
    });

    window.TLLocation = { init: initAll };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else {
        initAll(document);
    }
})();
