@extends('layouts.user')

@section('title', 'Danh sách yêu thích')

@section('content')
<style>.star-rating { color: var(--tl-amber, #F0A500); letter-spacing: 1px; }</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-bold text-secondary mb-0">❤️ Danh sách yêu thích</h4>
    <span class="text-muted small">{{ $items->total() }} sản phẩm đã lưu</span>
</div>

<div class="row g-4">
    @forelse ($items as $item)
    @php $product = $item->product; @endphp
    @continue(!$product)
    <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <a href="{{ route('products.show', $product->id) }}">
                @if ($product->image)
                <img src="{{ $product->image_url }}" class="card-img-top rounded-top-4" style="height: 180px; object-fit: cover;" alt="{{ $product->name }}">
                @else
                <div class="d-flex align-items-center justify-content-center" style="height: 180px; background: linear-gradient(135deg, var(--tl-primary-100), var(--tl-surface-2));"><span style="font-size:2.6rem">🧸</span></div>
                @endif
            </a>
            <div class="card-body d-flex flex-column">
                <span class="badge bg-info text-dark align-self-start mb-2">{{ $product->category->name ?? 'Chưa phân loại' }}</span>
                <a href="{{ route('products.show', $product->id) }}" class="text-decoration-none text-dark">
                    <h6 class="fw-bold mb-1">{{ $product->name }}</h6>
                </a>
                <div class="small text-muted mb-2">{{ $product->brand ?: '' }}</div>

                <div class="mt-auto d-flex justify-content-between align-items-center mb-2">
                    <span class="text-danger fw-bold">{{ number_format($product->price, 0, ',', '.') }} đ</span>
                    @if ($product->quantity > 0)
                    <span class="badge bg-success">Còn hàng</span>
                    @else
                    <span class="badge bg-danger">Hết hàng</span>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    @if ($product->quantity > 0 && $product->has_variants)
                    <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-primary w-100 rounded-pill flex-grow-1">Chọn phân loại</a>
                    @elseif ($product->quantity > 0)
                    <form action="{{ route('cart.add', $product->id) }}" method="POST" class="flex-grow-1 ajax-add-to-cart-form">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary w-100 rounded-pill">Thêm vào giỏ</button>
                    </form>
                    @endif
                    <form action="{{ route('wishlist.destroy', $item->id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" title="Bỏ khỏi yêu thích">✕</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="tl-empty col-12">
        <div class="fs-1 mb-2">💔</div>
        Danh sách yêu thích của bạn đang trống.
        <div class="mt-3"><a href="{{ route('home') }}" class="btn btn-outline-primary rounded-pill px-4">Khám phá đồ chơi</a></div>
    </div>
    @endforelse
</div>

<div class="d-flex justify-content-center mt-4">{{ $items->links() }}</div>
@endsection
