@extends('layout')

@section('title', 'Đăng ký thành công')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div class="display-1 mb-3">✉️</div>
            <h4 class="fw-bold text-secondary mb-3">Đăng ký thành công!</h4>

            <p class="text-muted">
                Chúng tôi đã gửi một liên kết xác thực đến email của bạn.
                Vui lòng kiểm tra <strong>Hộp thư đến</strong> (hoặc thư mục <strong>Spam/Thư rác</strong>)
                và bấm vào liên kết để hoàn tất đăng ký.
            </p>
            <p class="text-muted small mb-0">
                Bạn cần xác thực email trước khi có thể đăng nhập vào hệ thống.
            </p>

            <hr class="my-4">

            <p class="small text-muted mb-0">
                Đã xác thực thành công? <a href="{{ route('login') }}" class="fw-semibold">Bấm vào đây để Đăng nhập</a>
            </p>
        </div>
    </div>
</div>
@endsection
