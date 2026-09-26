@extends('layouts.user')

@section('title', 'Hồ sơ của bé')

@section('content')
<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">🧒 Hồ sơ của bé</h4>
            <p class="text-muted small mb-0">
                Khai ngày sinh và sở thích của bé, ToyLand sẽ gợi ý đồ chơi đúng lứa tuổi
                và nhắc bạn trước khi sinh nhật bé tới.
            </p>
        </div>

        <div class="d-flex gap-2">
            @if ($children->isNotEmpty())
            <a href="{{ route('children.birthdays') }}" class="btn btn-outline-primary rounded-pill px-4">
                <i class="bi bi-calendar-heart me-1"></i> Sổ sinh nhật
            </a>
            @endif
            <a href="{{ route('children.create') }}" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-lg me-1"></i> Thêm bé
            </a>
        </div>
    </div>

    @if ($children->isEmpty())
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body text-center py-5">
            <div style="font-size: 3.4rem; line-height: 1;">🧸</div>
            <h5 class="fw-bold mt-3 mb-2">Chưa có hồ sơ bé nào</h5>
            <p class="text-muted mb-4" style="max-width: 460px; margin: 0 auto;">
                Chỉ cần tên và ngày sinh là đủ. Từ đó ToyLand lọc sẵn đồ chơi đúng độ tuổi,
                và nhắc bạn trước {{ \App\Models\ChildProfile::REMIND_DAYS }} ngày khi sinh nhật bé sắp tới.
            </p>
            <a href="{{ route('children.create') }}" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-lg me-1"></i> Thêm bé đầu tiên
            </a>
        </div>
    </div>
    @endif

    @foreach ($children as $child)
    @php $isActive = optional($activeChild)->id === $child->id; @endphp

    <div class="card border-0 shadow-sm rounded-4 mb-4 {{ $isActive ? 'tl-child-active' : '' }}">
        <div class="card-body">

            {{-- ============ ĐẦU THẺ ============ --}}
            <div class="d-flex flex-wrap align-items-start gap-3">
                <div class="tl-child-avatar">{{ $child->avatar ?: '🧒' }}</div>

                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h5 class="fw-bold mb-0">{{ $child->name }}</h5>

                        @if ($isActive)
                        <span class="badge rounded-pill bg-success">Đang xem cho bé này</span>
                        @endif

                        @if ($child->is_birthday_soon)
                        <span class="badge rounded-pill bg-warning text-dark">
                            🎂 {{ $child->countdown_label }}
                        </span>
                        @endif
                    </div>

                    <div class="text-muted small mt-1">
                        {{ $child->age_label }}
                        @if ($child->gender)
                            · {{ \App\Models\ChildProfile::GENDERS[$child->gender] }}
                        @endif
                        · Sinh {{ $child->birthday->format('d/m/Y') }}
                    </div>

                    <div class="mt-2 d-flex flex-wrap gap-1">
                        @foreach ($child->interest_list as $interest)
                        <span class="badge rounded-pill tl-badge-cream">{{ $interest }}</span>
                        @endforeach

                        @if ($child->favoriteCategory)
                        <span class="badge rounded-pill tl-badge-cream">
                            {{ $child->favoriteCategory->icon }} {{ $child->favoriteCategory->name }}
                        </span>
                        @endif
                    </div>

                    @if ($child->note)
                    <div class="text-muted small mt-2 fst-italic">{{ $child->note }}</div>
                    @endif
                </div>

                {{-- ============ NÚT THAO TÁC ============ --}}
                <div class="d-flex flex-wrap gap-2">
                    @unless ($isActive)
                    <form action="{{ route('children.select', $child->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="bi bi-magic me-1"></i> Xem cho bé này
                        </button>
                    </form>
                    @else
                    <form action="{{ route('children.clear') }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3">Bỏ chọn</button>
                    </form>
                    @endunless

                    <a href="{{ route('children.edit', $child->id) }}"
                       class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-pencil"></i>
                    </a>

                    <form action="{{ route('children.destroy', $child->id) }}" method="POST"
                          onsubmit="return confirm('Xóa hồ sơ của {{ $child->name }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- ============ GỢI Ý CHO BÉ ============ --}}
            @php $picks = $suggestions[$child->id] ?? collect(); @endphp

            @if ($picks->isNotEmpty())
            <hr class="my-3">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="fw-semibold small">
                    <i class="bi bi-stars me-1"></i>
                    Hợp với {{ $child->name }} ({{ $child->age_label }})
                </div>
                <a href="{{ route('home', $child->filterQuery()) }}" class="small text-decoration-none">
                    Xem tất cả <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="row g-3">
                {{-- partial tự mang sẵn class col-* nên KHÔNG bọc thêm div cột --}}
                @foreach ($picks as $product)
                    @include('partials._mini-product', ['product' => $product])
                @endforeach
            </div>
            @endif

        </div>
    </div>
    @endforeach

</div>
@endsection
