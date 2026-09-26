@if ($errors->any())
<div class="alert alert-danger rounded-3 border-0">
    <ul class="mb-0 small">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label fw-semibold">Nhóm câu hỏi <span class="text-danger">*</span></label>
        <input type="text" name="category" list="faq-categories" value="{{ old('category', $faq->category ?? 'Chung') }}" class="form-control" required>
        <datalist id="faq-categories">
            <option value="Đặt hàng"><option value="Thanh toán"><option value="Vận chuyển">
            <option value="Đổi trả & Bảo hành"><option value="Sản phẩm"><option value="Tài khoản"><option value="Chung">
        </datalist>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Thứ tự hiển thị</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $faq->sort_order ?? 0) }}" min="0" class="form-control">
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Câu hỏi <span class="text-danger">*</span></label>
        <input type="text" name="question" value="{{ old('question', $faq->question ?? '') }}" class="form-control" maxlength="255" required>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Câu trả lời <span class="text-danger">*</span></label>
        <textarea name="answer" rows="6" class="form-control" required>{{ old('answer', $faq->answer ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="faqActive" {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="faqActive">Hiển thị trên trang FAQ</label>
        </div>
    </div>
</div>
