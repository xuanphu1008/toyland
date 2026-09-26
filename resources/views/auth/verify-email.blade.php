@extends('layout')

@section('title', 'Xác thực Email')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div class="display-4 mb-3">📧</div>
            <h4 class="fw-bold text-secondary mb-3">Kiểm tra hộp thư của bạn</h4>

            @if (session('success'))
            <div class="alert alert-success rounded-3">{{ session('success') }}</div>
            @endif
            @if (session('error'))
            <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
            @endif

            <p class="text-muted">
                <strong>Đăng ký thành công!</strong> Chúng tôi đã gửi một liên kết xác thực đến email của bạn.
                Vui lòng kiểm tra <strong>Hộp thư đến</strong> (hoặc thư mục <strong>Spam/Thư rác</strong>)
                để hoàn tất đăng ký.
            </p>
            <p class="text-muted small">
                Nếu bạn không nhận được email, hãy bấm nút bên dưới để chúng tôi gửi lại.
            </p>

            <form action="{{ route('verification.send') }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-primary rounded-pill px-4">Gửi lại email xác thực</button>
            </form>

            <hr class="my-4">

            <p class="text-muted small mb-2">Đã xác thực email ở một thiết bị/tab khác rồi?</p>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary rounded-pill px-4">
                    Đã xác thực? Đăng xuất và Quay lại Đăng nhập
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
