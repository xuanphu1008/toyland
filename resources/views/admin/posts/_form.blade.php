@if ($errors->any())
<div class="alert alert-danger rounded-3 border-0">
    <ul class="mb-0 small">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title', $post->title ?? '') }}" class="form-control" required maxlength="255">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Chuyên mục <span class="text-danger">*</span></label>
        <input type="text" name="category" list="post-categories" value="{{ old('category', $post->category ?? 'Tin tức') }}" class="form-control" required>
        <datalist id="post-categories">
            <option value="Tin tức"><option value="Cẩm nang chọn đồ chơi"><option value="Review sản phẩm">
            <option value="Chơi cùng con"><option value="Khuyến mãi">
        </datalist>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Tóm tắt</label>
        <textarea name="excerpt" rows="2" class="form-control" maxlength="500" placeholder="Bỏ trống sẽ tự cắt từ nội dung">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Nội dung <span class="text-danger">*</span></label>
        <textarea name="content" rows="14" class="form-control" required>{{ old('content', $post->content ?? '') }}</textarea>
    </div>

    <div class="col-md-5">
        <label class="form-label fw-semibold">Ảnh bìa</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        @if (!empty($post->image))
        <div class="mt-2"><img src="{{ $post->image_url }}" class="rounded border" width="140" alt=""></div>
        @endif
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Thời điểm đăng</label>
        <input type="datetime-local" name="published_at" class="form-control"
               value="{{ old('published_at', isset($post->published_at) ? $post->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}">
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="postPublished" {{ old('is_published', $post->is_published ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="postPublished">Xuất bản</label>
        </div>
    </div>
</div>
