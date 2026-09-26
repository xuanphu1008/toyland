@extends('layouts.user')

@section('title', 'Tạo yêu cầu hỗ trợ')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4 mx-auto" style="max-width: 760px;">
    <h5 class="fw-bold text-secondary mb-4">Gửi yêu cầu hỗ trợ</h5>

    <form action="{{ route('tickets.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
            <input type="text" name="subject" value="{{ old('subject') }}" class="form-control rounded-3 p-2" maxlength="255" required
                   placeholder="VD: Đơn hàng #12 bị giao thiếu 1 món">
            @error('subject')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Loại yêu cầu <span class="text-danger">*</span></label>
                <select name="category" class="form-select rounded-3 p-2" required>
                    @foreach (\App\Models\Ticket::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Mức độ <span class="text-danger">*</span></label>
                <select name="priority" class="form-select rounded-3 p-2" required>
                    @foreach (\App\Models\Ticket::PRIORITIES as $value => $label)
                    <option value="{{ $value }}" {{ old('priority', 'normal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Đơn hàng liên quan</label>
                <select name="order_id" class="form-select rounded-3 p-2">
                    <option value="">-- Không liên quan đơn nào --</option>
                    @foreach ($orders as $order)
                    <option value="{{ $order->id }}" {{ (string) old('order_id') === (string) $order->id ? 'selected' : '' }}>
                        #{{ $order->id }} - {{ number_format($order->total_price, 0, ',', '.') }} đ ({{ $order->created_at->format('d/m/Y') }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 mb-3">
            <label class="form-label fw-semibold">Nội dung chi tiết <span class="text-danger">*</span></label>
            <textarea name="content" rows="6" class="form-control rounded-3 p-2" maxlength="2000" required
                      placeholder="Mô tả càng chi tiết càng giúp ToyLand xử lý nhanh hơn...">{{ old('content') }}</textarea>
            @error('content')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex gap-2 pt-3 border-top">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Gửi yêu cầu</button>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Hủy</a>
        </div>
    </form>
</div>
@endsection
