@extends(Auth::check() && !Auth::user()->isAdmin() ? 'layouts.user' : 'layout')

@section('title', 'Câu hỏi thường gặp')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold" style="color: var(--tl-ink, #4A2F2F);">CÂU HỎI THƯỜNG GẶP</h3>
    <p class="text-muted">Những thắc mắc phổ biến nhất về mua sắm tại ToyLand</p>
</div>

<form action="{{ route('faq.index') }}" method="GET" class="mx-auto mb-4" style="max-width: 560px;">
    <div class="input-group">
        <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Nhập từ khóa: đổi trả, vận chuyển, thanh toán...">
        <button class="btn btn-primary px-4">Tìm</button>
        @if ($keyword !== '')
        <a href="{{ route('faq.index') }}" class="btn btn-outline-secondary">Xóa</a>
        @endif
    </div>
</form>

@forelse ($faqs as $category => $items)
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body">
        <h6 class="fw-bold text-uppercase text-primary mb-3">{{ $category }}</h6>

        <div class="accordion accordion-flush" id="faq-{{ $loop->index }}">
            @foreach ($items as $faq)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq-item-{{ $faq->id }}">
                        {{ $faq->question }}
                    </button>
                </h2>
                <div id="faq-item-{{ $faq->id }}" class="accordion-collapse collapse" data-bs-parent="#faq-{{ $loop->parent->index }}">
                    <div class="accordion-body text-muted" style="white-space: pre-line;">{{ $faq->answer }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@empty
<div class="tl-empty ">
    <div class="fs-1 mb-2">🔎</div>
    Không tìm thấy câu hỏi nào khớp với từ khóa của bạn.
</div>
@endforelse

<div class="card border-0 shadow-sm rounded-4 bg-primary text-white text-center p-4">
    <h6 class="fw-bold mb-2">Chưa tìm thấy câu trả lời bạn cần?</h6>
    <p class="mb-3 small">Gửi yêu cầu hỗ trợ, đội ngũ ToyLand sẽ phản hồi trong vòng 24 giờ làm việc.</p>
    <div>
        @auth
        <a href="{{ route('tickets.create') }}" class="btn btn-light rounded-pill px-4">Gửi yêu cầu hỗ trợ</a>
        @else
        <a href="{{ route('login') }}" class="btn btn-light rounded-pill px-4">Đăng nhập để gửi yêu cầu</a>
        @endauth
    </div>
</div>
@endsection
