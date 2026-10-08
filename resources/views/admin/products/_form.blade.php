{{--
    Form dùng chung cho Thêm mới & Chỉnh sửa sản phẩm.
    Biến truyền vào:
      $product    - model sản phẩm (null khi thêm mới)
      $categories - danh sách danh mục đã kèm quan hệ parent
--}}
@php
    $product = $product ?? null;
    $selectedSkills = $product ? $product->skill_list : [];
    $selectedSkills = old('skills', $selectedSkills);
@endphp

@if ($errors->any())
<div class="alert alert-danger rounded-3 border-0">
    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Chưa lưu được, vui lòng kiểm tra lại:</div>
    <ul class="mb-0 small">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="row g-4">
    {{-- ============ CỘT TRÁI: THÔNG TIN BÁN HÀNG ============ --}}
    <div class="col-lg-8">

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-box-seam me-1"></i> Thông tin cơ bản</h6>

                <div class="mb-3">
                    <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}"
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="VD: Bộ Xếp Hình Thành Phố 1200 Chi Tiết" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Danh mục <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">-- Chọn danh mục --</option>
                            @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ (string) old('category_id', $product->category_id ?? '') === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->parent_id ? '— ' : '' }}{{ $category->icon }} {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Dòng có dấu “—” ở đầu là danh mục con.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mã sản phẩm (SKU)</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}"
                               class="form-control" placeholder="VD: TL-LEG-001">
                    </div>
                </div>

                <div class="row g-3 mt-0">
                    <div class="col-md-6">
                        <label class="form-label">Thương hiệu / Hãng</label>
                        <input type="text" name="brand" value="{{ old('brand', $product->brand ?? '') }}"
                               class="form-control" list="tl-brand-list" placeholder="VD: LEGO, Bandai, Fisher-Price">
                        <datalist id="tl-brand-list">
                            @foreach (\App\Models\Product::whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand') as $b)
                            <option value="{{ $b }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nhân vật / Chủ đề</label>
                        <input type="text" name="character_name" value="{{ old('character_name', $product->character_name ?? '') }}"
                               class="form-control" list="tl-character-list" placeholder="VD: Marvel, Doraemon, Elsa, Pokémon">
                        <datalist id="tl-character-list">
                            @foreach (\App\Models\Product::whereNotNull('character_name')->where('character_name', '!=', '')->distinct()->orderBy('character_name')->pluck('character_name') as $c)
                            <option value="{{ $c }}"></option>
                            @endforeach
                        </datalist>
                        <div class="form-text">Để trống nếu sản phẩm không gắn với nhân vật nào.</div>
                    </div>
                </div>

                <div class="mb-0 mt-3">
                    <label class="form-label">Mô tả sản phẩm</label>
                    <textarea name="description" class="form-control" rows="5"
                              placeholder="Đặc điểm nổi bật, bé học được gì, cách chơi gợi ý, lưu ý khi dùng...">{{ old('description', $product->description ?? '') }}</textarea>
                    <div class="form-text">Viết 3-5 câu: bé chơi thế nào, học được gì, bố mẹ cần lưu ý gì.</div>
                </div>
            </div>
        </div>

        {{-- ============ ĐỘ TUỔI & ĐỐI TƯỢNG ============ --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-people me-1"></i> Độ tuổi &amp; đối tượng</h6>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tuổi tối thiểu (tháng)</label>
                        <input type="number" name="age_min_months" min="0" max="300"
                               value="{{ old('age_min_months', $product->age_min_months ?? '') }}"
                               class="form-control @error('age_min_months') is-invalid @enderror" placeholder="36">
                        @error('age_min_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tuổi tối đa (tháng)</label>
                        <input type="number" name="age_max_months" min="0" max="300"
                               value="{{ old('age_max_months', $product->age_max_months ?? '') }}"
                               class="form-control @error('age_max_months') is-invalid @enderror" placeholder="72">
                        @error('age_max_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phù hợp với</label>
                        <select name="gender" class="form-select">
                            <option value="">-- Không xác định --</option>
                            @foreach (\App\Models\Product::GENDERS as $value => $label)
                            <option value="{{ $value }}" {{ old('gender', $product->gender ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="alert alert-info border-0 rounded-3 small mt-3 mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Nhập theo <strong>tháng</strong> để bộ lọc “độ tuổi” ở trang khách chạy chính xác:
                    1 tuổi = 12, 3 tuổi = 36, 6 tuổi = 72, 12 tuổi = 144. Bỏ trống ô tối đa nghĩa là “trở lên”.
                </div>

                <div class="mb-3">
                    <label class="form-label">Nhãn độ tuổi hiển thị</label>
                    <input type="text" name="age_range" value="{{ old('age_range', $product->age_range ?? '') }}"
                           class="form-control" placeholder="Để trống sẽ tự sinh, VD: 3 - 6 tuổi">
                </div>

                <label class="form-label">Kỹ năng bé rèn luyện</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach (\App\Models\Product::SKILL_OPTIONS as $skill)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="skills[]" value="{{ $skill }}"
                               id="skill-{{ $loop->index }}" {{ in_array($skill, (array) $selectedSkills, true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="skill-{{ $loop->index }}">{{ $skill }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ============ THÔNG SỐ KỸ THUẬT ============ --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-rulers me-1"></i> Thông số kỹ thuật</h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Chất liệu</label>
                        <input type="text" name="material" value="{{ old('material', $product->material ?? '') }}"
                               class="form-control" placeholder="VD: Nhựa ABS, Gỗ tự nhiên, Vải bông">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Xuất xứ</label>
                        <input type="text" name="origin" value="{{ old('origin', $product->origin ?? '') }}"
                               class="form-control" placeholder="VD: Việt Nam, Nhật Bản, Đan Mạch">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Số chi tiết</label>
                        <input type="number" name="piece_count" min="0" value="{{ old('piece_count', $product->piece_count ?? '') }}"
                               class="form-control" placeholder="VD: 1200">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kích thước</label>
                        <input type="text" name="dimensions" value="{{ old('dimensions', $product->dimensions ?? '') }}"
                               class="form-control" placeholder="VD: 38 x 26 x 7 cm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cân nặng (gram)</label>
                        <input type="number" name="weight_grams" min="0" value="{{ old('weight_grams', $product->weight_grams ?? '') }}"
                               class="form-control" placeholder="VD: 900">
                        <div class="form-text">Dùng để tính phí vận chuyển.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pin / Nguồn điện</label>
                        <input type="text" name="battery" value="{{ old('battery', $product->battery ?? '') }}"
                               class="form-control" placeholder="VD: 2 pin AA (không kèm theo)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bảo hành (tháng)</label>
                        <input type="number" name="warranty_months" min="0" max="120"
                               value="{{ old('warranty_months', $product->warranty_months ?? '') }}"
                               class="form-control" placeholder="VD: 6">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Lưu ý an toàn / Chứng nhận</label>
                        <input type="text" name="safety_note" value="{{ old('safety_note', $product->safety_note ?? '') }}"
                               class="form-control" placeholder="VD: Đạt CR, chi tiết nhỏ - không dành cho bé dưới 3 tuổi">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ CỘT PHẢI: GIÁ, KHO, ẢNH ============ --}}
    @push('scripts')
    <script>
    (function () {
        var wrap = document.getElementById('tl-variant-rows');
        if (!wrap) return;
        var idx = wrap.querySelectorAll('.tl-variant-row').length;
        var qty = document.getElementById('tl-qty-field');
        var note = document.getElementById('tl-qty-note');

        function recalc() {
            var rows = wrap.querySelectorAll('.tl-variant-row');
            if (!rows.length) { qty.readOnly = false; note.style.display = 'none'; return; }
            var sum = 0;
            rows.forEach(function (r) { sum += parseInt(r.querySelector('.v-qty').value, 10) || 0; });
            qty.value = sum; qty.readOnly = true; note.style.display = '';
        }
        document.getElementById('tl-add-variant').addEventListener('click', function () {
            var html = document.getElementById('tl-variant-tpl').innerHTML.replace(/__I__/g, idx++);
            wrap.insertAdjacentHTML('beforeend', html);
            recalc();
        });
        wrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.tl-del-variant');
            if (btn) { btn.closest('.tl-variant-row').remove(); recalc(); }
        });
        wrap.addEventListener('input', function (e) { if (e.target.classList.contains('v-qty')) recalc(); });
    })();
    </script>
    @endpush

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin me-1"></i> Giá &amp; kho</h6>

                <div class="mb-3">
                    <label class="form-label">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" name="price" min="0" step="1000" required
                           value="{{ old('price', $product->price ?? 0) }}"
                           class="form-control @error('price') is-invalid @enderror">
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @php
                    $variantRows = old('variants', $product ? $product->variants->map(fn ($v) => [
                        'id' => $v->id, 'name' => $v->name, 'sku' => $v->sku,
                        'price_adjust' => $v->price_adjust, 'quantity' => $v->quantity,
                    ])->all() : []);
                    $hasVariants = count($variantRows) > 0;
                @endphp
                <div class="mb-3">
                    <label class="form-label">Số lượng kho <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" min="0" required id="tl-qty-field"
                           value="{{ old('quantity', $product->quantity ?? 0) }}"
                           class="form-control @error('quantity') is-invalid @enderror" {{ $hasVariants ? 'readonly' : '' }}>
                    <div class="form-text" id="tl-qty-note" style="{{ $hasVariants ? '' : 'display:none' }}">
                        Sản phẩm có phân loại: kho = tổng số lượng các phân loại bên dưới.
                    </div>
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

 
                {{-- ===== Phân loại (biến thể) ===== --}}
                <div class="border rounded-3 p-2 mb-3 bg-light">
                    <div class="fw-semibold small mb-1"><i class="bi bi-palette me-1"></i> Phân loại (màu, cỡ, phiên bản)</div>
                    <div class="form-text mb-2">
                        Để trống nếu sản phẩm chỉ có một loại. "Chênh lệch giá" cộng/trừ vào giá bán
                        (VD: +20000 hoặc -10000); khuyến mãi flash sale vẫn áp cho cả sản phẩm.
                    </div>
                    <div id="tl-variant-rows">
                        @foreach ($variantRows as $i => $row)
                        <div class="tl-variant-row border rounded-3 bg-white p-2 mb-2">
                            <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                            <input type="text" name="variants[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}"
                                   class="form-control form-control-sm mb-1" placeholder="Tên phân loại, VD: Màu đỏ / Size M" maxlength="120">
                            <div class="d-flex gap-1">
                                <input type="number" name="variants[{{ $i }}][price_adjust]" value="{{ $row['price_adjust'] ?? 0 }}"
                                       class="form-control form-control-sm" placeholder="Chênh lệch giá" step="1000" title="Chênh lệch giá (đ)">
                                <input type="number" name="variants[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? 0 }}" min="0"
                                       class="form-control form-control-sm v-qty" placeholder="Số lượng" title="Số lượng kho">
                                <button type="button" class="btn btn-sm btn-outline-danger tl-del-variant" title="Xóa phân loại"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" id="tl-add-variant" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="bi bi-plus-lg me-1"></i> Thêm phân loại
                    </button>
                    <template id="tl-variant-tpl">
                        <div class="tl-variant-row border rounded-3 bg-white p-2 mb-2">
                            <input type="hidden" name="variants[__I__][id]" value="">
                            <input type="text" name="variants[__I__][name]" class="form-control form-control-sm mb-1"
                                   placeholder="Tên phân loại, VD: Màu đỏ / Size M" maxlength="120">
                            <div class="d-flex gap-1">
                                <input type="number" name="variants[__I__][price_adjust]" value="0" class="form-control form-control-sm" step="1000" title="Chênh lệch giá (đ)">
                                <input type="number" name="variants[__I__][quantity]" value="0" min="0" class="form-control form-control-sm v-qty" title="Số lượng kho">
                                <button type="button" class="btn btn-sm btn-outline-danger tl-del-variant" title="Xóa phân loại"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured"
                           {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_featured">Đánh dấu sản phẩm nổi bật</label>
                </div>
            </div>
        </div>

        {{-- ============ FLASH SALE ============ --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-1">⚡ Giá sốc có hạn</h6>
                <p class="text-muted small mb-3">
                    Để trống giá khuyến mãi là tắt chương trình. Hệ thống tự bật và tự tắt
                    đúng khung giờ, không cần ai ngồi canh.
                </p>

                <div class="mb-3">
                    <label class="form-label">Giá khuyến mãi (VNĐ)</label>
                    <input type="number" name="sale_price" min="0" step="1000"
                           value="{{ old('sale_price', $product->sale_price ?? null) }}"
                           class="form-control @error('sale_price') is-invalid @enderror"
                           placeholder="Để trống = không khuyến mãi">
                    @error('sale_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Phải nhỏ hơn giá bán gốc.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Bắt đầu</label>
                    <input type="datetime-local" name="sale_starts_at"
                           value="{{ old('sale_starts_at', optional($product->sale_starts_at ?? null)->format('Y-m-d\TH:i')) }}"
                           class="form-control @error('sale_starts_at') is-invalid @enderror">
                    @error('sale_starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Để trống = giảm giá ngay lập tức.</div>
                </div>

                <div class="mb-1">
                    <label class="form-label">Kết thúc</label>
                    <input type="datetime-local" name="sale_ends_at"
                           value="{{ old('sale_ends_at', optional($product->sale_ends_at ?? null)->format('Y-m-d\TH:i')) }}"
                           class="form-control @error('sale_ends_at') is-invalid @enderror">
                    @error('sale_ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Để trống = không có hạn, nhưng khi đó sẽ không hiện đồng hồ đếm ngược.</div>
                </div>

                @if (! empty($product) && $product->exists && $product->is_on_sale)
                <div class="alert alert-success small mt-3 mb-0">
                    Đang chạy khuyến mãi: giảm {{ $product->discount_percent }}%,
                    khách tiết kiệm {{ number_format($product->sale_saving, 0, ',', '.') }} đ.
                </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-image me-1"></i> Hình ảnh</h6>

                @if (! empty($product->image))
                <div class="mb-3 text-center">
                    <img src="{{ $product->image_url }}" class="rounded-3 border" style="max-width: 100%; max-height: 180px; object-fit: contain;" alt="">
                    <div class="form-check mt-2 text-start">
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                        <label class="form-check-label small" for="remove_image">Xóa ảnh hiện tại</label>
                    </div>
                </div>
                @endif

                <input type="file" name="image" id="tl-image-input"
                       class="form-control @error('image') is-invalid @enderror"
                       accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.avif">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="form-text">
                    Nhận {{ implode(', ', \App\Services\ImageUploadService::ALLOWED_EXTENSIONS) }} —
                    tối đa {{ \App\Services\ImageUploadService::MAX_KILOBYTES / 1024 }}MB.
                </div>

                {{-- Xem trước ngay trên trình duyệt, không cần bấm Lưu --}}
                <div id="tl-image-preview" class="mt-3 d-none text-center">
                    <img src="" class="rounded-3 border" style="max-width: 100%; max-height: 180px; object-fit: contain;" alt="Ảnh xem trước">
                    <div class="small text-muted mt-1" id="tl-image-info"></div>
                </div>

                {{-- ============ THƯ VIỆN ẢNH PHỤ ============ --}}
                <hr class="my-3">

                <h6 class="fw-bold mb-1">Ảnh phụ</h6>
                <p class="text-muted small mb-2">
                    Ảnh ở trên là ảnh đại diện (hiện ở danh sách, giỏ hàng, hóa đơn).
                    Ảnh phụ chỉ hiện trong thư viện ở trang chi tiết.
                </p>

                @if (! empty($product) && $product->exists && $product->images->isNotEmpty())
                <div class="tl-admin-gallery mb-3">
                    @foreach ($product->images as $image)
                    <div class="tl-admin-gallery-item">
                        <img src="{{ $image->url }}" alt="">
                        {{-- Form xóa ảnh phải nằm NGOÀI form sản phẩm (HTML cấm
                             lồng form), nên dùng thuộc tính form="" trỏ ngược lên
                             một form riêng đặt ở cuối trang. --}}
                        <button type="submit" class="tl-admin-gallery-del"
                                form="del-image-{{ $image->id }}" title="Xóa ảnh này">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif

                <input type="file" name="gallery[]" multiple
                       class="form-control @error('gallery.*') is-invalid @enderror"
                       accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.avif">
                @error('gallery.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('gallery')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">Chọn nhiều file cùng lúc, tối đa 8 ảnh mỗi lần.</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('tl-image-input');
        var box = document.getElementById('tl-image-preview');
        if (!input || !box) return;

        var img = box.querySelector('img');
        var info = document.getElementById('tl-image-info');
        var maxKb = {{ \App\Services\ImageUploadService::MAX_KILOBYTES }};

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { box.classList.add('d-none'); return; }

            var kb = Math.round(file.size / 1024);
            img.src = URL.createObjectURL(file);
            box.classList.remove('d-none');
            info.textContent = file.name + ' — ' + (kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB');

            // Cảnh báo sớm ngay trên trình duyệt thay vì để server trả lỗi
            if (kb > maxKb) {
                info.innerHTML += ' <span class="text-danger fw-semibold">(vượt quá ' + (maxKb / 1024) + 'MB)</span>';
            }
        });
    })();
</script>
@endpush
