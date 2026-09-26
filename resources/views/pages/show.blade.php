@extends(Auth::check() && !Auth::user()->isAdmin() ? 'layouts.user' : 'layout')

@section('title', $page->title)

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Trang chủ</a></li>
        <li class="breadcrumb-item active text-muted">{{ $page->title }}</li>
    </ol>
</nav>

<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
    <h3 class="fw-bold text-secondary mb-4">{{ $page->title }}</h3>
    <div class="text-body" style="white-space: pre-line; line-height: 1.8;">{{ $page->content }}</div>
    <div class="text-muted small mt-4 pt-3 border-top">
        Cập nhật lần cuối: {{ $page->updated_at->format('d/m/Y') }}
    </div>
</div>
@endsection
