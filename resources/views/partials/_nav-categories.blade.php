{{--
    Menu "Danh mục" dạng mega dropdown trên navbar.
    Dữ liệu $navCategories do View Composer trong AppServiceProvider chia sẻ.
    Menu này thay thế cho trang /danh-muc trước đây (đã bỏ vì trùng lặp với bộ lọc trang chủ).
--}}
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ request()->routeIs('home') && request('category') ? 'active' : '' }}"
       href="#" id="navCategoryMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-grid me-1"></i> Danh mục
    </a>

    <div class="dropdown-menu tl-mega-menu p-3" aria-labelledby="navCategoryMenu">
        {{-- Các cột nằm trong khối riêng để phần chân menu không bị cuốn vào multi-column --}}
        <div class="tl-mega-cols">
            @forelse ($navCategories as $root)
            <div class="tl-mega-col">
                <a href="{{ route('home', ['category' => $root->id]) }}" class="tl-mega-title">
                    <span class="tl-mega-icon">{{ $root->icon }}</span> {{ $root->name }}
                </a>

                @foreach ($root->children as $child)
                <a href="{{ route('home', ['category' => $child->id]) }}" class="tl-mega-link">{{ $child->name }}</a>
                @endforeach
            </div>
            @empty
            <div class="text-muted small px-2 py-1">Chưa có danh mục nào.</div>
            @endforelse
        </div>

        <div class="tl-mega-footer">
            <a href="{{ route('home') }}" class="fw-semibold text-decoration-none">
                <i class="bi bi-arrow-right-circle me-1"></i> Xem toàn bộ đồ chơi
            </a>
        </div>
    </div>
</li>
