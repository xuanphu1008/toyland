<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sinh nhật {{ $child->name }}</title>
</head>
<body style="margin:0;padding:0;background:#FFF8F2;font-family:Segoe UI,Tahoma,Arial,sans-serif;color:#4A2F2F;">
    <div style="max-width:620px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.06);">

        <div style="background:#E03131;color:#fff;padding:24px;text-align:center;">
            <div style="font-size:22px;font-weight:bold;">🧸 ToyLand</div>
            <div style="font-size:14px;opacity:.9;margin-top:4px;">
                @if ($child->days_until_birthday === 0)
                    Hôm nay là sinh nhật {{ $child->name }}!
                @else
                    Còn {{ $child->days_until_birthday }} ngày nữa là sinh nhật {{ $child->name }}
                @endif
            </div>
        </div>

        <div style="padding:24px;">
            <p>Xin chào <strong>{{ $child->user->name ?? 'quý khách' }}</strong>,</p>

            <p style="line-height:1.6;">
                <span style="font-size:20px;">{{ $child->avatar ?: '🧒' }}</span>
                <strong>{{ $child->name }}</strong>
                @if ($child->days_until_birthday === 0)
                    tròn <strong>{{ $child->turning_age }} tuổi</strong> hôm nay.
                @else
                    sắp tròn <strong>{{ $child->turning_age }} tuổi</strong> vào ngày
                    <strong>{{ $child->next_birthday->format('d/m/Y') }}</strong>.
                @endif
            </p>

            @if ($gifts->isNotEmpty())
            <p style="line-height:1.6;">
                ToyLand đã chọn sẵn vài món hợp với bé ở tuổi {{ $child->turning_age }} —
                tất cả đều còn hàng:
            </p>

            <table style="width:100%;border-collapse:collapse;margin:18px 0;font-size:14px;">
                @foreach ($gifts as $gift)
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #FBE1D8;">
                        <a href="{{ route('products.show', $gift->id) }}"
                           style="color:#D62B2B;text-decoration:none;font-weight:bold;">
                            {{ $gift->name }}
                        </a>
                        <div style="font-size:12px;color:#8B6F6F;margin-top:3px;">
                            {{ $gift->age_label ?? '' }}
                            @if ($gift->character_name) · {{ $gift->character_name }} @endif
                        </div>
                    </td>
                    <td style="padding:10px;border-bottom:1px solid #FBE1D8;text-align:right;white-space:nowrap;">
                        <strong>{{ number_format($gift->price, 0, ',', '.') }} đ</strong>
                    </td>
                </tr>
                @endforeach
            </table>
            @endif

            <div style="text-align:center;margin:26px 0;">
                <a href="{{ route('children.birthdays') }}"
                   style="display:inline-block;background:#E03131;color:#fff;text-decoration:none;
                          padding:12px 26px;border-radius:24px;font-weight:bold;">
                    Xem thêm gợi ý quà
                </a>
            </div>

            <p style="font-size:13px;color:#8B6F6F;line-height:1.6;">
                Bạn nhận được email này vì đã lưu ngày sinh của bé trong mục
                <strong>Hồ sơ của bé</strong>. Muốn dừng nhận nhắc nhở, bạn chỉ cần xóa hồ sơ đó.
            </p>
        </div>

        <div style="background:#FFF1EA;padding:16px;text-align:center;font-size:12px;color:#8B6F6F;">
            ToyLand — Thế giới đồ chơi cho bé · Hotline 1900 6868
        </div>
    </div>
</body>
</html>
