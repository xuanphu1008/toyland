@extends('errors.layout', [
    'code' => '419',
    'emoji' => '⏳',
    'title' => 'Phiên làm việc đã hết hạn',
    'message' => 'Bạn để trang mở quá lâu nên phiên đăng nhập hết hạn. Tải lại trang rồi thao tác lại là được, dữ liệu bạn vừa nhập không bị mất ở giỏ hàng.',
    'actions' => [
        ['label' => 'Tải lại trang', 'url' => url()->current(), 'primary' => true],
        ['label' => 'Về trang chủ', 'url' => url('/')],
    ],
])
