@extends('errors.layout', [
    'code' => '503',
    'emoji' => '🔧',
    'title' => 'Shop đang bảo trì',
    'message' => 'ToyLand đang được nâng cấp trong chốc lát. Bạn quay lại sau vài phút nhé, đồ chơi vẫn ở đó chờ bé.',
    'actions' => [
        ['label' => 'Về trang chủ', 'url' => url('/'), 'primary' => true],
    ],
])
