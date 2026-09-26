@extends('errors.layout', [
    'code' => '404',
    'emoji' => '🧭',
    'title' => 'Không tìm thấy trang',
    'message' => 'Trang bạn tìm không còn tồn tại hoặc đường dẫn bị gõ sai. Có thể món đồ chơi đó đã được gỡ khỏi cửa hàng.',
    'actions' => [
        ['label' => 'Về trang chủ', 'url' => url('/'), 'primary' => true],
        ['label' => 'Xem đồ chơi bán chạy', 'url' => url('/?sort=best_selling')],
    ],
])
