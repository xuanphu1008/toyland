@extends('layouts.admin')

@section('title', 'Ticket ' . $ticket->code)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold text-secondary mb-0">{{ $ticket->subject }}</h5>
        <div class="small text-muted">
            <span class="font-monospace">{{ $ticket->code }}</span>
            · {{ $ticket->category_label }}
            · Mức độ {{ $ticket->priority_label }}
            · {{ $ticket->created_at->format('d/m/Y H:i') }}
        </div>
    </div>
    <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary rounded-pill px-4">← Danh sách</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Khách hàng</h6>
                <div class="mb-2"><span class="text-muted small">Tên:</span><br><span class="fw-semibold">{{ $ticket->user->name ?? '--' }}</span></div>
                <div class="mb-2"><span class="text-muted small">Email:</span><br>{{ $ticket->user->email ?? '--' }}</div>
                <div class="mb-0">
                    <span class="text-muted small">Hạng thành viên:</span><br>
                    <span class="badge {{ $ticket->user->tier_info['color'] ?? 'bg-light text-dark' }}">
                        {{ $ticket->user->tier_info['icon'] ?? '' }} {{ $ticket->user->tier_label ?? '--' }}
                    </span>
                </div>
            </div>
        </div>

        @if ($ticket->order)
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Đơn hàng liên quan</h6>
                <div class="mb-2">
                    <a href="{{ route('admin.orders.show', $ticket->order_id) }}" class="fw-bold">#{{ $ticket->order_id }}</a>
                    <span class="badge {{ $ticket->order->status_color }} ms-1">{{ $ticket->order->status_label }}</span>
                </div>
                <div class="text-danger fw-bold">{{ number_format($ticket->order->total_price, 0, ',', '.') }} đ</div>
            </div>
        </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Trạng thái xử lý</h6>
                <form action="{{ route('admin.tickets.status', $ticket->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <select name="status" class="form-select mb-2">
                        @foreach (\App\Models\Ticket::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ $ticket->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary w-100 rounded-pill">Cập nhật trạng thái</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex gap-3 mb-4">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                    {{ mb_substr($ticket->user->name ?? 'K', 0, 1) }}
                </div>
                <div class="bg-light rounded-4 p-3 flex-grow-1">
                    <div class="fw-semibold small">{{ $ticket->user->name ?? 'Khách' }} <span class="text-muted fw-normal">· {{ $ticket->created_at->format('d/m/Y H:i') }}</span></div>
                    <div class="mt-1" style="white-space: pre-line;">{{ $ticket->content }}</div>
                </div>
            </div>

            @foreach ($ticket->replies as $reply)
            <div class="d-flex gap-3 mb-4 {{ $reply->is_staff ? 'flex-row-reverse' : '' }}">
                <div class="rounded-circle {{ $reply->is_staff ? 'bg-success' : 'bg-primary' }} text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                    {{ mb_substr($reply->user->name ?? '?', 0, 1) }}
                </div>
                <div class="{{ $reply->is_staff ? 'bg-success bg-opacity-10' : 'bg-light' }} rounded-4 p-3 flex-grow-1">
                    <div class="fw-semibold small">
                        {{ $reply->is_staff ? ($reply->user->name ?? 'Nhân viên') . ' (ToyLand)' : ($reply->user->name ?? 'Khách') }}
                        <span class="text-muted fw-normal">· {{ $reply->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="mt-1" style="white-space: pre-line;">{{ $reply->content }}</div>
                </div>
            </div>
            @endforeach

            <form action="{{ route('admin.tickets.reply', $ticket->id) }}" method="POST" class="border-top pt-3">
                @csrf
                <label class="form-label fw-semibold">Phản hồi khách hàng</label>
                <textarea name="content" rows="4" class="form-control mb-2" maxlength="2000" required placeholder="Nhập nội dung phản hồi..."></textarea>
                <button class="btn btn-primary rounded-pill px-4">Gửi phản hồi</button>
            </form>
        </div>
    </div>
</div>
@endsection
