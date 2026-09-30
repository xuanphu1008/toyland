@extends('errors.layout', [
    'code' => '405',
    'emoji' => '🔁',
    'title' => 'Thao tác này không mở trực tiếp được',
    'message' => 'Trang bạn vừa mở chỉ dùng khi bấm nút trên web (ví dụ gửi biểu mẫu). Thường gặp khi tải lại trang hoặc bấm Back sau khi gửi biểu mẫu. Quay lại trang trước rồi thao tác lại là được.',
    'actions' => [
        ['label' => 'Về trang chủ', 'url' => url('/'), 'primary' => true],
    ],
])
