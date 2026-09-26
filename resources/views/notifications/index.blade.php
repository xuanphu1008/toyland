@extends('layouts.user')

@section('title', 'Thông báo')

@section('content')
<div class="container py-4" style="max-width: 820px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">🔔 Thông báo</h4>
            <p class="text-muted small mb-0">
                @if ($unreadCount > 0)
                    Bạn có <strong>{{ $unreadCount }}</strong> thông báo chưa đọc.
                @else
                    Bạn đã đọc hết thông báo.
                @endif
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('notifications.index') }}"
               class="btn btn-sm rounded-pill px-3 {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                Tất cả
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
               class="btn btn-sm rounded-pill px-3 {{ $filter === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}">
                Chưa đọc @if ($unreadCount > 0) ({{ $unreadCount }}) @endif
            </a>

            @if ($unreadCount > 0)
            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-check2-all me-1"></i> Đánh dấu đã đọc
                </button>
            </form>
            @endif

            @if ($notifications->total() > 0)
            <form action="{{ route('notifications.destroyAll') }}" method="POST"
                  onsubmit="return confirm('Xóa toàn bộ thông báo?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3">
                    <i class="bi bi-trash me-1"></i> Dọn sạch
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            @forelse ($notifications as $noti)
            <div class="tl-noti-row {{ $noti->is_unread ? 'tl-noti-unread' : '' }}">
                <a href="{{ route('notifications.open', $noti->id) }}" class="tl-noti-row-link">
                    <span class="tl-noti-icon">{{ $noti->icon }}</span>

                    <span class="flex-grow-1 min-w-0">
                        <span class="tl-noti-title">{{ $noti->title }}</span>
                        @if ($noti->body)
                        <span class="tl-noti-body">{{ $noti->body }}</span>
                        @endif
                        <span class="tl-noti-time">
                            {{ optional($noti->created_at)->diffForHumans() }}
                            · {{ optional($noti->created_at)->format('H:i d/m/Y') }}
                        </span>
                    </span>

                    @if ($noti->is_unread)
                    <span class="tl-noti-dot" title="Chưa đọc"></span>
                    @endif
                </a>

                <form action="{{ route('notifications.destroy', $noti->id) }}" method="POST" class="flex-shrink-0">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-link text-muted" title="Xóa thông báo này">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </form>
            </div>
            @empty
            <div class="tl-empty py-5">
                <span class="tl-empty-icon">🔔</span>
                {{ $filter === 'unread' ? 'Không còn thông báo chưa đọc nào.' : 'Chưa có thông báo nào.' }}
            </div>
            @endforelse
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $notifications->links() }}
    </div>

</div>
@endsection
