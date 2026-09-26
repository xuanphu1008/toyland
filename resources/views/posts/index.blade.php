@extends(Auth::check() && !Auth::user()->isAdmin() ? 'layouts.user' : 'layout')

@section('title', 'Tin tức & Blog đồ chơi')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold" style="color: var(--tl-ink, #4A2F2F);">TIN TỨC & CẨM NANG ĐỒ CHƠI</h3>
    <p class="text-muted">Chọn đồ chơi đúng độ tuổi, chơi cùng con đúng cách</p>
</div>

<form action="{{ route('posts.index') }}" method="GET" class="row g-2 justify-content-center mb-4">
    <div class="col-md-5">
        <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Tìm bài viết...">
    </div>
    <div class="col-md-3">
        <select name="category" class="form-select">
            <option value="">-- Tất cả chuyên mục --</option>
            @foreach ($categories as $cat)
            <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100">Tìm bài viết</button>
    </div>
</form>

<div class="row g-4">
    @forelse ($posts as $post)
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <a href="{{ route('posts.show', $post->slug) }}">
                @if ($post->image)
                <img src="{{ $post->image_url }}" class="card-img-top rounded-top-4" style="height: 190px; object-fit: cover;" alt="{{ $post->title }}">
                @else
                <div class="bg-light rounded-top-4 d-flex align-items-center justify-content-center" style="height: 190px;"><span class="fs-1">📰</span></div>
                @endif
            </a>
            <div class="card-body d-flex flex-column">
                <span class="badge bg-info text-dark align-self-start mb-2">{{ $post->category }}</span>
                <a href="{{ route('posts.show', $post->slug) }}" class="text-decoration-none text-dark">
                    <h6 class="fw-bold">{{ $post->title }}</h6>
                </a>
                <p class="text-muted small flex-grow-1">{{ $post->summary }}</p>
                <div class="text-muted small d-flex justify-content-between">
                    <span>{{ optional($post->published_at)->format('d/m/Y') }}</span>
                    <span>{{ $post->views }} lượt xem</span>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="tl-empty col-12">
        <div class="fs-1 mb-2">📭</div>
        Chưa có bài viết nào.
    </div>
    @endforelse
</div>

<div class="d-flex justify-content-center mt-4">{{ $posts->links() }}</div>
@endsection
