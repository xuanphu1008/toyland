{{--
    Chuông thông báo trên thanh điều hướng.

    Dữ liệu ($myNotifications, $unreadNotifications) do View Composer '*' trong
    AppServiceProvider chia sẻ, nên partial này dùng được ở bất kỳ layout nào.

    Con số trên chuông được vẽ sẵn từ server khi tải trang, và tự cập nhật mỗi
    60 giây bằng một lệnh gọi JSON rất nhẹ (xem _scripts.blade.php).
--}}
<div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary rounded-pill position-relative px-3"
            type="button" data-bs-toggle="dropdown" title="Thông báo" aria-label="Thông báo">
        <i class="bi bi-bell{{ ($unreadNotifications ?? 0) > 0 ? '-fill' : '' }}"></i>

        <span class="badge bg-danger tl-pill-count position-absolute top-0 start-100 translate-middle
                     {{ ($unreadNotifications ?? 0) > 0 ? '' : 'd-none' }}"
              data-tl-noti-count>{{ $unreadNotifications ?? 0 }}</span>
    </button>

    <div class="dropdown-menu dropdown-menu-end tl-noti-menu">

        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="fw-bold small">Thông báo</span>

            @if (($unreadNotifications ?? 0) > 0)
            <form action="{{ route('notifications.readAll') }}" method="POST" class="m-0">
                @csrf
                <button class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: .74rem;">
                    Đánh dấu đã đọc
                </button>
            </form>
            @endif
        </div>

        <div class="tl-noti-list">
            @forelse ($myNotifications ?? collect() as $noti)
            <a href="{{ route('notifications.open', $noti->id) }}"
               class="tl-noti-item {{ $noti->is_unread ? 'tl-noti-unread' : '' }}">
                <span class="tl-noti-icon">{{ $noti->icon }}</span>

                <span class="flex-grow-1 min-w-0">
                    <span class="tl-noti-title">{{ $noti->title }}</span>
                    @if ($noti->body)
                    <span class="tl-noti-body">{{ \Illuminate\Support\Str::limit($noti->body, 70) }}</span>
                    @endif
                    <span class="tl-noti-time">{{ optional($noti->created_at)->diffForHumans() }}</span>
                </span>

                @if ($noti->is_unread)
                <span class="tl-noti-dot" title="Chưa đọc"></span>
                @endif
            </a>
            @empty
            <div class="text-center text-muted py-4 px-3">
                <div style="font-size: 1.8rem;">🔔</div>
                <div class="small mt-2">Chưa có thông báo nào.</div>
            </div>
            @endforelse
        </div>

        @if (($myNotifications ?? collect())->isNotEmpty())
        <div class="border-top text-center py-2">
            <a href="{{ route('notifications.index') }}" class="small text-decoration-none">
                Xem tất cả thông báo
            </a>
        </div>
        @endif
    </div>
</div>
