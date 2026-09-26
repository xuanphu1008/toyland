@extends(Auth::check() && !Auth::user()->isAdmin() ? 'layouts.user' : 'layout')

@section('title', 'So sánh sản phẩm')

@section('content')
<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">⚖️ So sánh sản phẩm</h4>
            <p class="text-muted small mb-0">
                Đặt cạnh nhau tối đa {{ \App\Http\Controllers\CompareController::MAX_ITEMS }} món
                để thấy rõ khác nhau ở đâu.
            </p>
        </div>

        @if ($products->isNotEmpty())
        <form action="{{ route('compare.clear') }}" method="POST">
            @csrf
            <button class="btn btn-outline-danger rounded-pill px-4">
                <i class="bi bi-x-circle me-1"></i> Xóa hết
            </button>
        </form>
        @endif
    </div>

    @if ($products->isEmpty())
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body text-center py-5">
            <div style="font-size: 3.4rem; line-height: 1;">⚖️</div>
            <h5 class="fw-bold mt-3 mb-2">Chưa chọn món nào để so sánh</h5>
            <p class="text-muted mb-4" style="max-width: 470px; margin: 0 auto;">
                Bấm nút <i class="bi bi-bar-chart-steps"></i> trên mỗi thẻ sản phẩm để thêm vào đây.
                So sánh giúp bạn thấy ngay khác biệt về độ tuổi, chất liệu, số chi tiết và giá.
            </p>
            <a href="{{ route('home') }}" class="btn btn-primary rounded-pill px-4">Xem đồ chơi</a>
        </div>
    </div>
    @else

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table tl-compare-table mb-0">

                {{-- ============ HÀNG ĐẦU: ẢNH, TÊN, NÚT ============ --}}
                <thead>
                    <tr>
                        <th class="tl-compare-label">Sản phẩm</th>

                        @foreach ($products as $product)
                        <th class="text-center">
                            <div class="position-relative">
                                <form action="{{ route('compare.remove', $product->id) }}" method="POST"
                                      class="position-absolute top-0 end-0">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-link text-muted p-0" title="Bỏ khỏi so sánh">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>

                                <a href="{{ route('products.show', $product->id) }}" class="d-block">
                                    @if ($product->image)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                         class="tl-compare-img" loading="lazy">
                                    @else
                                    <div class="tl-compare-img d-flex align-items-center justify-content-center"
                                         style="background: var(--tl-surface-2);">
                                        <span style="font-size: 2rem;">🧸</span>
                                    </div>
                                    @endif
                                </a>

                                @if ($product->is_on_sale)
                                <span class="badge bg-danger rounded-pill mb-1">−{{ $product->discount_percent }}%</span>
                                @endif

                                <a href="{{ route('products.show', $product->id) }}"
                                   class="d-block fw-semibold text-decoration-none tl-compare-name">
                                    {{ \Illuminate\Support\Str::limit($product->name, 52) }}
                                </a>

                                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="mt-2">
                                    @csrf
                                    <button class="btn btn-sm btn-primary rounded-pill px-3 w-100"
                                            {{ $product->quantity > 0 ? '' : 'disabled' }}>
                                        {{ $product->quantity > 0 ? 'Thêm vào giỏ' : 'Hết hàng' }}
                                    </button>
                                </form>
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>

                {{-- ============ CÁC HÀNG THÔNG SỐ ============ --}}
                <tbody>
                    @foreach ($rows as $row)
                    @php
                        [$label, $values] = [$row[0], $row[1]];
                        $isMoney = $row[2] ?? false;
                        // Giá trị giống hệt nhau ở mọi cột thì làm mờ đi - mắt
                        // người chỉ cần nhìn vào chỗ KHÁC nhau
                        $allSame = count(array_unique($values)) === 1 && count($values) > 1;
                    @endphp

                    <tr class="{{ $allSame ? 'tl-compare-same' : '' }}">
                        <td class="tl-compare-label">{{ $label }}</td>

                        @foreach ($values as $value)
                        <td class="text-center {{ $isMoney ? 'fw-bold' : '' }}"
                            style="{{ $isMoney ? 'color: var(--tl-danger);' : '' }}">
                            {{ $value }}
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Những hàng có giá trị giống nhau ở mọi sản phẩm được làm mờ để bạn tập trung vào chỗ khác biệt.
    </p>
    @endif

</div>
@endsection
