@extends('errors.layout', [
    'code' => '429',
    'emoji' => '🐢',
    'title' => 'Bạn thao tác hơi nhanh',
    'message' => 'Hệ thống nhận quá nhiều yêu cầu từ máy bạn trong thời gian ngắn. Chờ khoảng một phút rồi thử lại nhé.',
    'actions' => [
        ['label' => 'Về trang chủ', 'url' => url('/'), 'primary' => true],
    ],
])
