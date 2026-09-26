@extends('layouts.user')

@section('title', $child->exists ? 'Sửa hồ sơ bé' : 'Thêm hồ sơ bé')

@section('content')
<div class="container py-4" style="max-width: 760px;">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('children.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">{{ $child->exists ? 'Sửa hồ sơ của ' . $child->name : 'Thêm hồ sơ bé' }}</h4>
    </div>

    <form action="{{ $child->exists ? route('children.update', $child->id) : route('children.store') }}"
          method="POST">
        @csrf
        @if ($child->exists) @method('PUT') @endif

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">

                {{-- ============ AVATAR ============ --}}
                <label class="form-label fw-semibold">Chọn hình đại diện</label>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @foreach ($avatars as $emoji)
                    <label class="tl-avatar-pick">
                        <input type="radio" name="avatar" value="{{ $emoji }}" class="d-none"
                               {{ old('avatar', $child->avatar ?: '🧒') === $emoji ? 'checked' : '' }}>
                        <span>{{ $emoji }}</span>
                    </label>
                    @endforeach
                </div>

                <div class="row g-3">
                    {{-- ============ TÊN ============ --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tên bé <span class="text-danger">*</span></label>
                        <input type="text" name="name" maxlength="60"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $child->name) }}" placeholder="Ví dụ: Bé An" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- ============ NGÀY SINH ============ --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ngày sinh <span class="text-danger">*</span></label>
                        <input type="date" name="birthday" max="{{ now()->toDateString() }}"
                               class="form-control @error('birthday') is-invalid @enderror"
                               value="{{ old('birthday', optional($child->birthday)->format('Y-m-d')) }}" required>
                        @error('birthday') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            Dùng để gợi ý đúng độ tuổi và nhắc bạn trước
                            {{ \App\Models\ChildProfile::REMIND_DAYS }} ngày khi sinh nhật tới.
                        </div>
                    </div>

                    {{-- ============ GIỚI TÍNH ============ --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giới tính</label>
                        <select name="gender" class="form-select">
                            <option value="">Không phân biệt</option>
                            @foreach ($genders as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('gender', $child->gender) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Để trống thì gợi ý cả đồ chơi trung tính.</div>
                    </div>

                    {{-- ============ DANH MỤC YÊU THÍCH ============ --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nhóm đồ chơi bé thích nhất</label>
                        <select name="favorite_category_id" class="form-select">
                            <option value="">Chưa rõ</option>
                            @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ (int) old('favorite_category_id', $child->favorite_category_id) === $category->id ? 'selected' : '' }}>
                                {{ $category->icon }} {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ============ NHÂN VẬT ============ --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nhân vật bé yêu thích</label>
                        <p class="form-text mt-0 mb-2">
                            Chọn tối đa 8 nhân vật. Danh sách lấy từ chính hàng đang bán nên
                            chọn gì cũng có đồ chơi tương ứng.
                        </p>

                        @php $chosen = old('interests', $child->interest_list ?? []); @endphp

                        <div class="tl-interest-box">
                            @foreach ($characters as $character)
                            <label class="tl-interest-chip">
                                <input type="checkbox" name="interests[]" value="{{ $character }}" class="d-none"
                                       {{ in_array($character, (array) $chosen, true) ? 'checked' : '' }}>
                                <span>{{ $character }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('interests') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('interests.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    {{-- ============ GHI CHÚ ============ --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <input type="text" name="note" maxlength="255" class="form-control"
                               value="{{ old('note', $child->note) }}"
                               placeholder="Ví dụ: bé đang tập lắp ghép, không thích đồ có tiếng động lớn">
                    </div>
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-check-lg me-1"></i>
                {{ $child->exists ? 'Lưu thay đổi' : 'Thêm bé' }}
            </button>
            <a href="{{ route('children.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy</a>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
/* Giới hạn 8 nhân vật ngay trên trình duyệt để khách biết trước khi bấm Lưu.
   Server vẫn kiểm tra lại bằng luật 'max:8' - phía trình duyệt chỉ là tiện lợi,
   không bao giờ được coi là lớp bảo vệ. */
(function () {
    var MAX = 8;
    var box = document.querySelector('.tl-interest-box');
    if (!box) return;

    box.addEventListener('change', function (e) {
        if (e.target.type !== 'checkbox') return;

        var checked = box.querySelectorAll('input[type=checkbox]:checked');
        if (checked.length > MAX) {
            e.target.checked = false;
            alert('Chỉ chọn tối đa ' + MAX + ' nhân vật thôi bạn nhé.');
        }
    });
})();
</script>
@endpush
