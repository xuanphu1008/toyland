@extends('layouts.admin')

@section('title', 'Chatbot')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-robot me-1"></i> Trợ lý tự động</h4>
        <p class="text-muted small mb-0">Theo dõi chất lượng bot và tìm những câu bot chưa trả lời được.</p>
    </div>

    <span class="badge rounded-pill {{ $llmEnabled ? 'bg-success' : 'bg-secondary' }} px-3 py-2">
        {{ $llmEnabled ? 'Đã bật LLM bổ trợ' : 'Chạy hoàn toàn offline' }}
    </span>
</div>

{{-- ============ AI BỔ TRỢ (GEMINI): HẠN MỨC & KIỂM TRA ============ --}}
@if ($llmEnabled)
@php
    $q = $llmQuota;
    $usedPct = $q['day_limit'] > 0 ? min(100, round($q['today'] / $q['day_limit'] * 100)) : 0;
@endphp
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h6 class="fw-bold mb-1">🤖 AI bổ trợ: {{ $llmDriver }} {{ $llmModel ? '· ' . $llmModel : '' }}</h6>
                <p class="text-muted small mb-0">
                    Chỉ những câu đúng chủ đề mà bot nội bộ chưa trả lời được mới gửi lên AI;
                    thông tin cá nhân được che trước khi gửi.
                </p>
            </div>
            <form action="{{ route('admin.chatbot.test-llm') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plug me-1"></i> Kiểm tra kết nối AI
                </button>
            </form>
        </div>

        <div class="row g-3 mt-1 small">
            <div class="col-md-5">
                <div class="d-flex justify-content-between">
                    <span>Lượt gọi hôm nay (theo giờ Google)</span>
                    <strong>{{ number_format($q['today']) }} / {{ number_format($q['day_limit']) }}</strong>
                </div>
                <div class="progress mt-1" style="height: 6px;">
                    <div class="progress-bar {{ $usedPct >= 90 ? 'bg-danger' : ($usedPct >= 70 ? 'bg-warning' : 'bg-success') }}"
                         style="width: {{ $usedPct }}%"></div>
                </div>
                <div class="text-muted mt-1">
                    Đặt lại sau khoảng {{ gmdate('G', $q['reset_in']) }} giờ {{ gmdate('i', $q['reset_in']) }} phút.
                </div>
            </div>
            <div class="col-md-3">
                <div>Phút này: <strong>{{ $q['minute'] }} / {{ $q['minute_limit'] }}</strong></div>
                <div>Mỗi khách/ngày: <strong>{{ $q['per_client_limit'] }}</strong> lượt</div>
            </div>
            <div class="col-md-4">
                @if ($q['cooldown'] > 0)
                    <div class="text-danger fw-semibold">⏸ Đang tạm dừng gọi AI thêm {{ $q['cooldown'] }} giây</div>
                @else
                    <div class="text-success fw-semibold">● Sẵn sàng</div>
                @endif
                @if ($q['last_error'])
                    <div class="text-muted mt-1">
                        Lỗi gần nhất ({{ $q['last_error']['at'] }}): {{ $q['last_error']['message'] }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

{{-- ============ SỐ LIỆU TỔNG QUAN ============ --}}
<div class="row g-3 mb-4">
    @php
    $cards = [
        ['label' => 'Hội thoại', 'value' => number_format($stats['conversations'], 0, ',', '.'), 'icon' => '💬', 'note' => 'Tổng số phiên chat'],
        ['label' => 'Tin nhắn', 'value' => number_format($stats['messages'], 0, ',', '.'), 'icon' => '✉️', 'note' => 'Cả khách lẫn bot'],
        ['label' => 'Bot tự xử lý', 'value' => $stats['resolve_rate'] . '%', 'icon' => '🎯', 'note' => 'Câu trả lời hiểu được ý khách'],
        ['label' => 'Chuyển nhân viên', 'value' => number_format($stats['handoffs'], 0, ',', '.'), 'icon' => '🙋', 'note' => 'Hội thoại bot phải nhường'],
    ];
    @endphp

    @foreach ($cards as $card)
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-muted small">{{ $card['label'] }}</div>
                    <span style="font-size: 1.4rem">{{ $card['icon'] }}</span>
                </div>
                <div class="fw-bold" style="font-size: 1.7rem; line-height: 1.2;">{{ $card['value'] }}</div>
                <div class="text-muted" style="font-size: .74rem;">{{ $card['note'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4">
    {{-- ============ CÂU BOT CHƯA HIỂU ============ --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Bot chưa trả lời được</h6>
                <p class="text-muted small mb-3">
                    Đây là việc cần làm: thêm những câu này vào
                    <a href="{{ route('admin.faqs.index') }}">Câu hỏi thường gặp</a>
                    là lần sau bot tự trả lời, không phải sửa code.
                </p>

                @forelse ($unanswered as $row)
                <div class="d-flex justify-content-between align-items-start gap-2 py-2 border-bottom">
                    <div class="min-w-0">
                        <div class="fw-semibold small">{{ $row['question'] }}</div>
                        <div class="text-muted" style="font-size: .74rem;">
                            Hỏi lần cuối {{ optional($row['last_asked'])->diffForHumans() }}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        @if ($row['count'] > 1)
                        <span class="badge bg-warning text-dark">{{ $row['count'] }} lần</span>
                        @endif
                        <a href="{{ route('admin.chatbot.show', $row['conversation_id']) }}"
                           class="btn btn-sm btn-outline-primary rounded-pill">Xem</a>
                    </div>
                </div>
                @empty
                <div class="tl-empty py-4">
                    <span class="tl-empty-icon">🎉</span>
                    Chưa có câu nào bot chịu thua.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ============ THỐNG KÊ Ý ĐỊNH ============ --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1"><i class="bi bi-bar-chart me-1"></i> Khách hỏi gì nhiều nhất</h6>
                <p class="text-muted small mb-3">
                    Ý định bot nhận diện được, kèm độ tin cậy trung bình.
                </p>

                @php $maxTotal = $intentStats->max('total') ?: 1; @endphp

                @forelse ($intentStats as $row)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold">{{ $row['label'] }}</span>
                        <span class="small text-muted">
                            {{ $row['total'] }} lượt · tin cậy {{ $row['confidence'] }}%
                        </span>
                    </div>
                    <div class="progress" style="height: 7px;">
                        <div class="progress-bar {{ $row['intent'] === 'unknown' ? 'bg-danger' : '' }}"
                             style="width: {{ round($row['total'] / $maxTotal * 100) }}%"></div>
                    </div>
                </div>
                @empty
                <div class="tl-empty py-4">
                    <span class="tl-empty-icon">📊</span>
                    Chưa có dữ liệu. Hãy thử trò chuyện với bot ở trang khách hàng.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ============ DANH SÁCH HỘI THOẠI ============ --}}
<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="fw-bold mb-0">Hội thoại gần đây</h6>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.chatbot.index') }}"
                   class="btn btn-sm rounded-pill {{ request('only_handoff') ? 'btn-outline-secondary' : 'btn-primary' }}">Tất cả</a>
                <a href="{{ route('admin.chatbot.index', ['only_handoff' => 1]) }}"
                   class="btn btn-sm rounded-pill {{ request('only_handoff') ? 'btn-primary' : 'btn-outline-secondary' }}">Đã chuyển nhân viên</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Khách</th>
                        <th>Câu hỏi đầu tiên</th>
                        <th class="text-center">Số tin</th>
                        <th class="text-center">Trạng thái</th>
                        <th>Gần nhất</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($conversations as $conversation)
                    <tr>
                        <td class="fw-semibold">
                            {{ $conversation->user->name ?? 'Khách vãng lai' }}
                            <div class="text-muted" style="font-size: .72rem;">#{{ $conversation->id }}</div>
                        </td>
                        <td class="small">{{ $conversation->title }}</td>
                        <td class="text-center">{{ $conversation->message_count }}</td>
                        <td class="text-center">
                            @if ($conversation->handed_to_human)
                            <span class="badge bg-warning text-dark">Chuyển nhân viên</span>
                            @else
                            <span class="badge bg-success">Bot xử lý</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ optional($conversation->last_message_at)->diffForHumans() }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.chatbot.show', $conversation->id) }}"
                               class="btn btn-sm btn-outline-primary rounded-pill">Xem</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Chưa có hội thoại nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $conversations->links() }}
        </div>
    </div>
</div>
@endsection
