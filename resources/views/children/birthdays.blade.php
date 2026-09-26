@extends('layouts.user')

@section('title', 'Sổ sinh nhật')

@section('content')
<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">🎂 Sổ sinh nhật</h4>
            <p class="text-muted small mb-0">
                Quà bên dưới được chọn theo độ tuổi bé <strong>sắp tròn</strong>, không phải tuổi hiện tại —
                để món quà còn hợp với bé suốt cả năm sau đó.
            </p>
        </div>

        <a href="{{ route('children.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-people me-1"></i> Hồ sơ của bé
        </a>
    </div>

    @forelse ($children as $child)
    @php
        $picks = $suggestions[$child->id] ?? collect();
        $soon = $child->is_birthday_soon;
    @endphp

    <div class="card border-0 shadow-sm rounded-4 mb-4 {{ $soon ? 'tl-birthday-soon' : '' }}">
        <div class="card-body">

            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="tl-child-avatar">{{ $child->avatar ?: '🧒' }}</div>

                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold mb-1">{{ $child->name }}</h5>
                    <div class="text-muted small">
                        Sinh nhật {{ $child->next_birthday->format('d/m') }}
                        · Hiện {{ $child->age_label }}
                    </div>
                </div>

                <div class="text-end">
                    <div class="tl-countdown {{ $soon ? 'tl-countdown-hot' : '' }}">
                        {{ $child->countdown_label }}
                    </div>
                    <div class="small text-muted mt-1">
                        Sắp tròn <strong>{{ $child->turning_age }} tuổi</strong>
                    </div>
                </div>
            </div>

            @if ($picks->isNotEmpty())
            <hr class="my-3">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="fw-semibold small">
                    <i class="bi bi-gift me-1"></i>
                    Gợi ý quà cho {{ $child->name }} tuổi {{ $child->turning_age }}
                </div>
                <a href="{{ route('home', $child->filterQuery($child->gift_age_months)) }}"
                   class="small text-decoration-none">
                    Xem tất cả đồ tuổi {{ $child->turning_age }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="row g-3">
                {{-- partial tự mang sẵn class col-* nên KHÔNG bọc thêm div cột --}}
                @foreach ($picks as $product)
                    @include('partials._mini-product', ['product' => $product])
                @endforeach
            </div>
            @else
            <hr class="my-3">
            <div class="text-muted small">
                Chưa tìm được món nào thật hợp tuổi {{ $child->turning_age }}.
                Bạn thử <a href="{{ route('home') }}">xem toàn bộ đồ chơi</a> nhé.
            </div>
            @endif

        </div>
    </div>
    @empty
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body text-center py-5">
            <div style="font-size: 3.4rem; line-height: 1;">🎈</div>
            <h5 class="fw-bold mt-3 mb-2">Sổ sinh nhật còn trống</h5>
            <p class="text-muted mb-4">Thêm hồ sơ bé kèm ngày sinh là sổ này tự có nội dung.</p>
            <a href="{{ route('children.create') }}" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-lg me-1"></i> Thêm bé
            </a>
        </div>
    </div>
    @endforelse

</div>
@endsection
