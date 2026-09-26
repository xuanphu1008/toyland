{{--
    Bảng thông số kỹ thuật dùng chung cho trang chi tiết (khách) và trang xem nhanh (admin).
    Chỉ hiện những dòng thực sự có dữ liệu để bảng không bị loãng vì "Đang cập nhật".
    Biến: $product
--}}
@php
    $specs = collect([
        ['icon' => 'bi-upc-scan',      'label' => 'Mã sản phẩm',   'value' => $product->sku],
        ['icon' => 'bi-award',         'label' => 'Thương hiệu',   'value' => $product->brand],
        ['icon' => 'bi-stars',         'label' => 'Nhân vật / Chủ đề', 'value' => $product->character_name],
        ['icon' => 'bi-cake2',         'label' => 'Độ tuổi phù hợp',   'value' => $product->age_label, 'highlight' => true],
        ['icon' => 'bi-gender-ambiguous', 'label' => 'Phù hợp với', 'value' => $product->gender ? $product->gender_label : null],
        ['icon' => 'bi-hammer',        'label' => 'Chất liệu',     'value' => $product->material],
        ['icon' => 'bi-puzzle',        'label' => 'Số chi tiết',   'value' => $product->piece_count ? number_format($product->piece_count, 0, ',', '.') . ' chi tiết' : null],
        ['icon' => 'bi-battery-half',  'label' => 'Pin / Nguồn điện', 'value' => $product->battery],
        ['icon' => 'bi-rulers',        'label' => 'Kích thước',    'value' => $product->dimensions],
        ['icon' => 'bi-box2',          'label' => 'Cân nặng',      'value' => $product->weight_grams ? number_format($product->weight_grams, 0, ',', '.') . ' g' : null],
        ['icon' => 'bi-globe-asia-australia', 'label' => 'Xuất xứ', 'value' => $product->origin],
        ['icon' => 'bi-shield-check',  'label' => 'Bảo hành',      'value' => $product->warranty_months ? $product->warranty_months . ' tháng' : null],
    ])->filter(fn ($row) => filled($row['value']));
@endphp

<table class="table table-sm align-middle mb-0 tl-spec-table">
    <tbody>
        @foreach ($specs as $row)
        <tr>
            <td class="text-muted" style="width: 42%;">
                <i class="bi {{ $row['icon'] }} me-1"></i> {{ $row['label'] }}
            </td>
            <td class="fw-semibold {{ ($row['highlight'] ?? false) ? 'text-success' : '' }}">{{ $row['value'] }}</td>
        </tr>
        @endforeach

        @if (empty($specs->count()))
        <tr>
            <td colspan="2" class="text-muted text-center py-3">Đang cập nhật thông số.</td>
        </tr>
        @endif
    </tbody>
</table>

@if ($product->skill_list)
<div class="mt-3">
    <div class="small text-muted mb-2"><i class="bi bi-lightbulb me-1"></i> Bé rèn luyện được</div>
    <div class="d-flex flex-wrap gap-2">
        @foreach ($product->skill_list as $skill)
        <span class="badge rounded-pill tl-skill-badge">{{ $skill }}</span>
        @endforeach
    </div>
</div>
@endif

@if ($product->safety_note)
<div class="alert alert-warning border-0 rounded-3 small mt-3 mb-0">
    <i class="bi bi-shield-exclamation me-1"></i> <strong>An toàn:</strong> {{ $product->safety_note }}
</div>
@endif
