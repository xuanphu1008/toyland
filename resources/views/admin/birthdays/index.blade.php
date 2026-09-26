@extends('layouts.admin')

@section('title', 'Sổ sinh nhật khách hàng')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-heart me-1"></i> Sổ sinh nhật khách hàng</h4>
        <p class="text-muted small mb-0">
            Danh sách bé sắp sinh nhật, xếp theo ngày gần nhất — dùng để gửi mã giảm giá đúng lúc.
        </p>
    </div>

    <div class="d-flex gap-2">
        @foreach ($ranges as $value => $label)
        <a href="{{ route('admin.birthdays.index', ['days' => $value]) }}"
           class="btn btn-sm rounded-pill {{ $days === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

{{-- ============ SỐ LIỆU ============ --}}
<div class="row g-3 mb-4">
    @php
    $cards = [
        ['label' => 'Hồ sơ bé', 'value' => number_format($stats['children'], 0, ',', '.'), 'icon' => '🧒', 'note' => 'Tổng số bé được khai'],
        ['label' => 'Gia đình', 'value' => number_format($stats['families'], 0, ',', '.'), 'icon' => '👨‍👩‍👧', 'note' => 'Tài khoản đã dùng tính năng'],
        ['label' => 'Sinh nhật tuần này', 'value' => number_format($stats['this_week'], 0, ',', '.'), 'icon' => '🎂', 'note' => 'Cần liên hệ ngay'],
        ['label' => 'Tuổi trung bình', 'value' => $stats['avg_age'], 'icon' => '📊', 'note' => 'Biết khách chủ yếu mua cho lứa nào'],
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
    {{-- ============ DANH SÁCH SẮP TỚI ============ --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Sắp tới sinh nhật ({{ $upcoming->count() }} bé)</h6>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Bé</th>
                                <th>Phụ huynh</th>
                                <th class="text-center">Ngày</th>
                                <th class="text-center">Còn</th>
                                <th class="text-center">Tròn</th>
                                <th>Sở thích</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($upcoming as $child)
                            <tr>
                                <td class="fw-semibold">
                                    <span class="me-1">{{ $child->avatar ?: '🧒' }}</span>{{ $child->name }}
                                    <div class="text-muted" style="font-size: .72rem;">
                                        {{ $child->age_label }}
                                        @if ($child->gender)
                                            · {{ \App\Models\ChildProfile::GENDERS[$child->gender] }}
                                        @endif
                                    </div>
                                </td>
                                <td class="small">
                                    {{ $child->user->name ?? '(tài khoản đã xóa)' }}
                                    <div class="text-muted" style="font-size: .72rem;">{{ $child->user->email ?? '' }}</div>
                                </td>
                                <td class="text-center small">{{ $child->next_birthday->format('d/m') }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill
                                        {{ $child->days_until_birthday <= 7 ? 'bg-danger' : 'bg-secondary' }}">
                                        {{ $child->days_until_birthday }} ngày
                                    </span>
                                </td>
                                <td class="text-center fw-semibold">{{ $child->turning_age }} tuổi</td>
                                <td>
                                    @forelse ($child->interest_list as $interest)
                                    <span class="badge rounded-pill tl-badge-cream">{{ $interest }}</span>
                                    @empty
                                    <span class="text-muted small">—</span>
                                    @endforelse
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Không có bé nào sinh nhật trong khoảng thời gian này.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ PHÂN BỐ THEO THÁNG ============ --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Sinh nhật theo tháng</h6>
                <p class="text-muted small mb-3">Tháng nào đông thì chuẩn bị hàng quà từ sớm.</p>

                @php $maxMonth = $byMonth->max() ?: 1; @endphp

                @forelse ($byMonth as $month => $count)
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold">Tháng {{ $month }}</span>
                        <span class="small text-muted">{{ $count }} bé</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar" style="width: {{ round($count / $maxMonth * 100) }}%"></div>
                    </div>
                </div>
                @empty
                <div class="tl-empty py-4">
                    <span class="tl-empty-icon">🎈</span>
                    Chưa có khách nào khai hồ sơ bé.
                </div>
                @endforelse

                <hr class="my-3">

                <div class="small text-muted">
                    <i class="bi bi-terminal me-1"></i>
                    Email nhắc sinh nhật gửi bằng lệnh
                    <code>php artisan toyland:birthday-reminder</code>.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
