@extends('layouts.user')

@section('title', 'Yêu cầu ' . $ticket->code)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold text-secondary mb-0">{{ $ticket->subject }}</h5>
        <div class="small text-muted">
            <span class="font-monospace">{{ $ticket->code }}</span>
            · {{ $ticket->category_label }}
            · Gửi lúc {{ $ticket->created_at->format('d/m/Y H:i') }}
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge {{ $ticket->status_color }} fs-6 px-3 py-2">{{ $ticket->status_label }}</span>
        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary rounded-pill px-4">← Danh sách</a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4">
    @if ($ticket->order)
    <div class="alert alert-light border rounded-3 small">
        Liên quan đơn hàng
        <a href="{{ route('orders.show', $ticket->order_id) }}" class="fw-semibold">#{{ $ticket->order_id }}</a>
        - {{ number_format($ticket->order->total_price, 0, ',', '.') }} đ
    </div>
    @endif

    {{-- Nội dung khách gửi ban đầu --}}
    <div class="d-flex gap-3 mb-4">
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
            {{ mb_substr(Auth::user()->name, 0, 1) }}
        </div>
        <div class="bg-light rounded-4 p-3 flex-grow-1">
            <div class="fw-semibold small">Bạn <span class="text-muted fw-normal">· {{ $ticket->created_at->format('d/m/Y H:i') }}</span></div>
            <div class="mt-1" style="white-space: pre-line;">{{ $ticket->content }}</div>
        </div>
    </div>

    {{-- Các lượt trả lời --}}
    @foreach ($ticket->replies as $reply)
    <div class="d-flex gap-3 mb-4 {{ $reply->is_staff ? '' : 'flex-row-reverse' }}">
        <div class="rounded-circle {{ $reply->is_staff ? 'bg-success' : 'bg-primary' }} text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
            {{ $reply->is_staff ? 'TL' : mb_substr($reply->user->name ?? 'B', 0, 1) }}
        </div>
        <div class="{{ $reply->is_staff ? 'bg-success bg-opacity-10' : 'bg-light' }} rounded-4 p-3 flex-grow-1">
            <div class="fw-semibold small">
                {{ $reply->is_staff ? 'ToyLand hỗ trợ' : 'Bạn' }}
                <span class="text-muted fw-normal">· {{ $reply->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="mt-1" style="white-space: pre-line;">{{ $reply->content }}</div>
        </div>
    </div>
    @endforeach

    @if ($ticket->isClosed())
    <div class="alert alert-secondary border-0 rounded-3 mb-0">
        Yêu cầu này đã đóng lúc {{ optional($ticket->closed_at)->format('d/m/Y H:i') }}.
        Cần hỗ trợ thêm, bạn hãy <a href="{{ route('tickets.create') }}">tạo yêu cầu mới</a>.
    </div>
    @else
    <form action="{{ route('tickets.reply', $ticket->id) }}" method="POST" class="border-top pt-3">
        @csrf
        <label class="form-label fw-semibold">Gửi phản hồi</label>
        <textarea name="content" rows="3" class="form-control mb-2" maxlength="2000" required placeholder="Nhập nội dung phản hồi..."></textarea>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Gửi phản hồi</button>
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                    onclick="if(confirm('Đóng yêu cầu này?')) document.getElementById('close-ticket').submit()">Đóng yêu cầu</button>
        </div>
    </form>
    <form id="close-ticket" action="{{ route('tickets.close', $ticket->id) }}" method="POST" class="d-none">
        @csrf @method('PATCH')
    </form>
    @endif
</div>
@endsection
