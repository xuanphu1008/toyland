{{-- Khối <head> dùng chung cho mọi layout: font, CSS, và đoạn script chống "nháy trắng" khi bật nền tối --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#FD5D5D">
<meta name="description" content="@yield('meta_description', 'ToyLand - Thế giới đồ chơi chính hãng, an toàn cho bé. Đồ chơi giáo dục, mô hình lắp ráp, búp bê, xe điều khiển và nhiều hơn nữa.')">

<title>@yield('title', 'ToyLand - Thế giới đồ chơi cho bé')</title>

{{-- Favicon dạng SVG inline: khối đồ chơi xếp hình, không cần file ảnh ngoài --}}
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='24' fill='%23FD5D5D'/><rect x='22' y='22' width='26' height='26' rx='7' fill='%23FFF7BC'/><rect x='52' y='22' width='26' height='26' rx='7' fill='%23FFFFFF'/><rect x='22' y='52' width='26' height='26' rx='7' fill='%23C0EDA6'/><rect x='52' y='52' width='26' height='26' rx='7' fill='%23FF8080'/></svg>">

{{-- Hai font: Baloo 2 cho tiêu đề (tròn trịa, hợp trẻ em), Be Vietnam Pro cho nội dung (tối ưu tiếng Việt) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('css/toyland.css') }}">

{{-- Đặt nền tối NGAY trước khi trang vẽ, nếu để trong toyland.js sẽ thấy chớp màu trắng --}}
<script>
    (function () {
        try {
            var saved = localStorage.getItem('toyland-theme');
            var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        } catch (e) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>

@stack('styles')
