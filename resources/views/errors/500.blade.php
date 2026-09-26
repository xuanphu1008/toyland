@extends('errors.layout', [
    'code' => '500',
    'emoji' => '🛠️',
    'title' => 'Hệ thống đang gặp trục trặc',
    'message' => 'Lỗi nằm ở phía shop chứ không phải do bạn. Đội kỹ thuật đã được ghi nhận, bạn thử lại sau ít phút giúp shop.',
    'actions' => [
        ['label' => 'Về trang chủ', 'url' => url('/'), 'primary' => true],
    ],
])
