@extends('errors.layout', [
    'code' => '403',
    'emoji' => '🔒',
    'title' => 'Bạn không có quyền xem trang này',
    'message' => 'Trang này thuộc về tài khoản khác hoặc dành riêng cho nhân viên. Nếu bạn nghĩ đây là nhầm lẫn, gọi 1900 6868 giúp shop nhé.',
    'actions' => [
        ['label' => 'Về trang chủ', 'url' => url('/'), 'primary' => true],
        ['label' => 'Đăng nhập tài khoản khác', 'url' => route('login')],
    ],
])
