@extends('layouts.admin')

@section('title', 'Chi tiết hội thoại')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h5 class="fw-bold mb-1">Hội thoại #{{ $conversation->id }}</h5>
        <p class="text-muted small mb-0">
            {{ $conversation->user->name ?? 'Khách vãng lai' }}
            · {{ $conversation->message_count }} tin nhắn
            · bắt đầu {{ optional($conversation->created_at)->format('H:i d/m/Y') }}
        </p>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.chatbot.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Quay lại
        </a>
        <form action="{{ route('admin.chatbot.destroy', $conversation->id) }}" method="POST"
              onsubmit="return confirm('Xóa hội thoại này?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger rounded-pill px-4">
                <i class="bi bi-trash me-1"></i> Xóa
            </button>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body" style="max-height: 640px; overflow-y: auto;">
                @forelse ($conversation->messages as $message)
                @php $isBot = $message->role === 'bot'; @endphp

                <div class="d-flex mb-3 {{ $isBot ? '' : 'justify-content-end' }}">
                    <div style="max-width: 78%;">
                        <div class="p-3 rounded-4"
                             style="white-space: pre-line;
                                    background: {{ $isBot ? 'var(--tl-surface-2)' : 'var(--tl-primary-100)' }};
                                    border: 1px solid var(--tl-border);">
                            {{ $message->content }}
                        </div>

                        <div class="small text-muted mt-1 {{ $isBot ? '' : 'text-end' }}">
                            {{ $isBot ? '🤖 Bot' : '👤 Khách' }}
                            · {{ optional($message->created_at)->format('H:i d/m') }}

                            @if ($isBot && $message->intent)
                            <span class="badge rounded-pill ms-1
                                {{ $message->intent === 'unknown' ? 'bg-danger' : 'bg-success' }}">
                                {{ $message->intent_label }}
                                @if ($message->confidence !== null)
                                    · {{ round($message->confidence * 100) }}%
                                @endif
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="tl-empty py-5">
                    <span class="tl-empty-icon">💬</span>
                    Hội thoại chưa có tin nhắn nào.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ============ BÓC TÁCH KỸ THUẬT ============ --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Bot đã "hiểu" thế nào</h6>
                <p class="text-muted small mb-3">
                    Thực thể bóc tách được và các ý định bot cân nhắc ở từng lượt trả lời.
                </p>

                @forelse ($conversation->messages->where('role', 'bot') as $message)
                @php $meta = $message->meta_data; @endphp

                @if (! empty($meta['entities']) || ! empty($meta['scores']))
                <div class="border rounded-3 p-3 mb-2">
                    <div class="small fw-semibold mb-2">{{ $message->intent_label }}</div>

                    @if (! empty($meta['entities']))
                    <div class="small mb-2">
                        <span class="text-muted">Bóc tách được:</span>
                        @foreach ($meta['entities'] as $key => $value)
                        <span class="badge rounded-pill tl-badge-cream me-1">{{ $key }}: {{ $value }}</span>
                        @endforeach
                    </div>
                    @endif

                    @if (! empty($meta['scores']))
                    <div class="small">
                        <span class="text-muted">Điểm các ý định:</span>
                        @foreach ($meta['scores'] as $intent => $score)
                        <div class="d-flex justify-content-between">
                            <span>{{ \App\Services\Chatbot\IntentMatcher::label($intent) }}</span>
                            <span class="fw-semibold">{{ round($score * 100) }}%</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif
                @empty
                <div class="text-muted small">Chưa có dữ liệu phân tích.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
