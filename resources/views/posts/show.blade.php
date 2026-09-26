@extends(Auth::check() && !Auth::user()->isAdmin() ? 'layouts.user' : 'layout')

@section('title', $post->title)

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="{{ route('posts.index') }}" class="text-decoration-none">Tin tức</a></li>
        <li class="breadcrumb-item active text-muted">{{ $post->title }}</li>
    </ol>
</nav>

<article class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
    <span class="badge bg-info text-dark align-self-start mb-2">{{ $post->category }}</span>
    <h3 class="fw-bold text-secondary">{{ $post->title }}</h3>

    <div class="text-muted small mb-4">
        {{ optional($post->published_at)->format('d/m/Y') }}
        · {{ $post->author->name ?? 'ToyLand' }}
        · {{ $post->views }} lượt xem
    </div>

    @if ($post->image)
    <img src="{{ $post->image_url }}" class="img-fluid rounded-4 mb-4" alt="{{ $post->title }}">
    @endif

    @if ($post->excerpt)
    <p class="lead text-muted">{{ $post->excerpt }}</p>
    @endif

    <div class="text-body" style="white-space: pre-line; line-height: 1.8;">{{ $post->content }}</div>
</article>

@if ($related->isNotEmpty())
<div class="mt-4">
    <h6 class="fw-bold text-uppercase mb-3" style="color: var(--tl-ink, #4A2F2F); letter-spacing: 1px;">Bài viết liên quan</h6>
    <div class="row g-3">
        @foreach ($related as $item)
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3">
                <a href="{{ route('posts.show', $item->slug) }}" class="text-decoration-none text-dark">
                    <h6 class="fw-bold small mb-1">{{ $item->title }}</h6>
                </a>
                <div class="text-muted small">{{ optional($item->published_at)->format('d/m/Y') }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
