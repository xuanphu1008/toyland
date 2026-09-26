{{-- Form dùng chung cho Thêm / Sửa danh mục. Biến: $category (có thể null), $parents --}}
@php $category = $category ?? null; @endphp

@if ($errors->any())
<div class="alert alert-danger rounded-3 border-0">
    <ul class="mb-0 small">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               placeholder="VD: Xe Điều Khiển Từ Xa" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Biểu tượng (emoji)</label>
        <input type="text" name="icon" value="{{ old('icon', $category->icon ?? '') }}"
               class="form-control" maxlength="8" placeholder="🚗">
        <div class="form-text">Hiện trên menu và thẻ danh mục.</div>
    </div>

    <div class="col-md-8">
        <label class="form-label">Danh mục cha</label>
        <select name="parent_id" class="form-select">
            <option value="">-- Là danh mục gốc --</option>
            @foreach ($parents as $parent)
            <option value="{{ $parent->id }}"
                {{ (string) old('parent_id', $category->parent_id ?? '') === (string) $parent->id ? 'selected' : '' }}>
                {{ $parent->icon }} {{ $parent->name }}
            </option>
            @endforeach
        </select>
        <div class="form-text">Hệ thống dùng cây 2 cấp nên chỉ chọn được danh mục gốc làm cha.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Thứ tự hiển thị</label>
        <input type="number" name="sort_order" min="0" max="9999"
               value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="form-control">
        <div class="form-text">Số nhỏ hiện trước.</div>
    </div>

    <div class="col-12">
        <label class="form-label">Mô tả</label>
        <textarea name="description" rows="3" class="form-control"
                  placeholder="Mô tả ngắn giúp phụ huynh hiểu danh mục này gồm những gì.">{{ old('description', $category->description ?? '') }}</textarea>
    </div>
</div>
