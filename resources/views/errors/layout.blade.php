{{--
    KHUNG CHUNG CHO CÁC TRANG LỖI

    CỐ Ý KHÔNG dùng layouts.user:
    Layout đó cần thanh điều hướng, giỏ hàng, chuông thông báo, khung chat -
    toàn những thứ phải truy vấn CSDL. Trang lỗi 500 thường xảy ra CHÍNH VÌ
    CSDL có vấn đề; nếu trang lỗi cũng cần CSDL thì nó hỏng theo, và người dùng
    nhận về một màn hình trắng không nói lên điều gì.

    Ở đây chỉ nạp file CSS tĩnh và vài biến màu nội tuyến - không chạm dữ liệu,
    nên luôn hiển thị được dù hệ thống đang hỏng tới đâu.

    Biến nhận vào:
      $code     mã lỗi hiển thị to
      $emoji    hình minh họa
      $title    tiêu đề ngắn
      $message  giải thích cho người dùng
      $actions  mảng [['label' => ..., 'url' => ..., 'primary' => bool], ...]
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} | ToyLand</title>
    <link rel="stylesheet" href="{{ asset('css/toyland.css') }}">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: #FFF8F2;
            color: #4A2F2F;
        }

        .box { max-width: 520px; text-align: center; }

        .emoji { font-size: 4.6rem; line-height: 1; }

        .code {
            font-size: 3.4rem;
            font-weight: 800;
            color: #D62B2B;
            letter-spacing: .04em;
            margin: 6px 0 2px;
        }

        h1 { font-size: 1.35rem; margin: 0 0 12px; }
        p  { font-size: .96rem; line-height: 1.65; color: #6B5555; margin: 0 0 26px; }

        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

        .btn {
            display: inline-block;
            padding: 11px 26px;
            border-radius: 999px;
            font-weight: 600;
            font-size: .94rem;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-main  { background: #D62B2B; color: #fff; }
        .btn-ghost { background: #fff; color: #4A2F2F; border-color: #E3D2CB; }

        .brand { margin-top: 32px; font-size: .84rem; color: #A89090; }

        @media (prefers-color-scheme: dark) {
            body { background: #241B1B; color: #FFEDE6; }
            p { color: #C9B3AD; }
            .btn-ghost { background: #332424; color: #FFEDE6; border-color: #4A3636; }
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="emoji">{{ $emoji }}</div>
        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>

        <div class="actions">
            @foreach ($actions as $action)
            <a href="{{ $action['url'] }}"
               class="btn {{ ($action['primary'] ?? false) ? 'btn-main' : 'btn-ghost' }}">
                {{ $action['label'] }}
            </a>
            @endforeach
        </div>

        <div class="brand">🧸 ToyLand — Thế giới đồ chơi cho bé</div>
    </div>
</body>
</html>
