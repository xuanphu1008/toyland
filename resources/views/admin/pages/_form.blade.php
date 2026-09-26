@if ($errors->any())
<div class="alert alert-danger rounded-3 border-0">
    <ul class="mb-0 small">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="row g-3">
    <div class="col-md-7">
        <label class="form-label fw-semibold">Tiêu đề trang <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title', $page->title ?? '') }}" class="form-control" required placeholder="VD: Về chúng tôi">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Đường dẫn (slug)</label>
        <input type="text" name="slug" value="{{ old('slug', $page->slug ?? '') }}" class="form-control" placeholder="tu-dong-sinh">
    </div>

    <div class="col-md-2">
        <label class="form-label fw-semibold">Thứ tự</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" min="0" class="form-control">
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Nội dung <span class="text-danger">*</span></label>
        <textarea name="content" rows="14" class="form-control" required>{{ old('content', $page->content ?? '') }}</textarea>
        <div class="form-text">Xuống dòng sẽ được giữ nguyên khi hiển thị.</div>
    </div>

    <div class="col-md-6">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="pagePublished" {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="pagePublished">Đăng trang này</label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="show_in_footer" value="1" id="pageFooter" {{ old('show_in_footer', $page->show_in_footer ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="pageFooter">Hiện link ở chân trang</label>
        </div>
    </div>
</div>
