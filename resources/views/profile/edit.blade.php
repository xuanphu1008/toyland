@extends(Auth::user()->isAdmin() ? 'layouts.admin' : 'layouts.user')

@section('title', 'Hồ sơ của tôi')

@section('content')
<h4 class="fw-bold text-secondary mb-4">Hồ sơ của tôi</h4>

{{-- Thẻ thống kê nhanh --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-muted small">Tổng số đơn</div>
            <div class="fs-4 fw-bold text-primary">{{ $stats['orders'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-muted small">Đơn đã nhận</div>
            <div class="fs-4 fw-bold text-success">{{ $stats['completed'] }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-muted small">Tổng chi tiêu</div>
            <div class="fs-5 fw-bold text-danger">{{ number_format($stats['spent'], 0, ',', '.') }} đ</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-muted small">Điểm tích lũy</div>
            <div class="fs-4 fw-bold text-warning">{{ number_format($user->points, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

{{-- ===== Thẻ hạng thành viên ===== --}}
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-md-4 text-center">
                <div class="display-4">{{ $user->tier_info['icon'] }}</div>
                <span class="badge {{ $user->tier_info['color'] }} fs-6 px-3 py-2">Hạng {{ $user->tier_label }}</span>
                @if ($user->tier_discount > 0)
                <div class="small text-success fw-semibold mt-2">Tự động giảm {{ $user->tier_discount }}% mọi đơn hàng</div>
                @else
                <div class="small text-muted mt-2">Mua thêm để mở khóa ưu đãi giảm giá</div>
                @endif
            </div>

            <div class="col-md-8">
                @php $next = $user->next_tier; @endphp
                @if ($next)
                @php
                $current = \App\Models\User::TIERS[$user->tier]['min'];
                $span = max(1, $next['min'] - $current);
                $percent = min(100, round(($user->lifetime_spent - $current) * 100 / $span));
                @endphp
                <div class="d-flex justify-content-between small mb-1">
                    <span>{{ $user->tier_info['icon'] }} {{ $user->tier_label }}</span>
                    <span>{{ $next['icon'] }} {{ $next['label'] }}</span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-warning" style="width: {{ $percent }}%"></div>
                </div>
                <div class="small text-muted">
                    Bạn đã chi tiêu <strong>{{ number_format($user->lifetime_spent, 0, ',', '.') }} đ</strong>.
                    Mua thêm <strong class="text-danger">{{ number_format($next['remaining'], 0, ',', '.') }} đ</strong>
                    để lên hạng {{ $next['label'] }}.
                </div>
                @else
                <div class="alert alert-success border-0 rounded-3 mb-0">
                    Chúc mừng! Bạn đang ở hạng cao nhất của ToyLand với ưu đãi giảm {{ $user->tier_discount }}% mọi đơn hàng.
                </div>
                @endif

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <a href="{{ route('wishlist.index') }}" class="btn btn-sm btn-outline-danger rounded-pill">❤️ Yêu thích ({{ $stats['wishlist'] }})</a>
                    <a href="{{ route('reviews.index') }}" class="btn btn-sm btn-outline-warning rounded-pill">⭐ Đánh giá ({{ $stats['reviews'] }})</a>
                    <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">🎧 Hỗ trợ ({{ $stats['tickets'] }})</a>
                    <a href="{{ route('coupons.index') }}" class="btn btn-sm btn-outline-success rounded-pill">🎟️ Mã giảm giá</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-3">
        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link {{ session('tab') !== 'password' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-info" type="button">Thông tin cá nhân</button>
            </li>
            <li class="nav-item">
                <button class="nav-link {{ session('tab') === 'password' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-password" type="button">Đổi mật khẩu</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-address" type="button">Sổ địa chỉ ({{ $addresses->count() }})</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-points" type="button">Điểm thưởng</button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content">
            {{-- ===== TAB 1: THÔNG TIN CÁ NHÂN ===== --}}
            <div class="tab-pane fade {{ session('tab') !== 'password' ? 'show active' : '' }}" id="tab-info">
                <form action="{{ route('profile.update') }}" method="POST" style="max-width: 560px;">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control rounded-3 p-2" required>
                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control rounded-3 p-2" required>
                        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <div class="form-text">Đổi email sẽ phải xác thực lại địa chỉ mới trước khi đặt hàng tiếp.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái tài khoản</label>
                        <div>
                            @if ($user->hasVerifiedEmail())
                            <span class="badge bg-success">Email đã xác thực</span>
                            @else
                            <span class="badge bg-warning text-dark">Chưa xác thực email</span>
                            @endif
                            <span class="badge bg-secondary ms-1">{{ $user->isAdmin() ? 'Quản trị viên' : 'Khách hàng' }}</span>
                            <span class="text-muted small ms-2">Tham gia {{ $user->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4">Lưu thay đổi</button>
                </form>
            </div>

            {{-- ===== TAB 2: ĐỔI MẬT KHẨU ===== --}}
            <div class="tab-pane fade {{ session('tab') === 'password' ? 'show active' : '' }}" id="tab-password">
                <form action="{{ route('profile.password') }}" method="POST" style="max-width: 460px;">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" class="form-control rounded-3 p-2" required>
                        @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu mới</label>
                        <input type="password" name="password" class="form-control rounded-3 p-2" required>
                        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nhập lại mật khẩu mới</label>
                        <input type="password" name="password_confirmation" class="form-control rounded-3 p-2" required>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4">Đổi mật khẩu</button>
                </form>
            </div>

            {{-- ===== TAB 3: SỔ ĐỊA CHỈ ===== --}}
            <div class="tab-pane fade" id="tab-address">
                <div class="row g-4">
                    <div class="col-lg-7">
                        @forelse ($addresses as $address)
                        <div class="border rounded-4 p-3 mb-3 {{ $address->is_default ? 'border-primary' : '' }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="fw-bold">{{ $address->name }}</span>
                                    <span class="text-muted">| {{ $address->phone }}</span>
                                    @if ($address->is_default)
                                    <span class="badge bg-primary ms-2">Mặc định</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-muted small mt-1">{{ $address->full_address }}</div>

                            @unless ($address->hasLocation())
                            <div class="small mt-1" style="color: var(--tl-warning);">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                Chưa có Tỉnh/Quận/Phường nên không tính được phí vận chuyển. Bấm “Sửa” để bổ sung.
                            </div>
                            @endunless

                            <div class="d-flex gap-2 mt-2">
                                @unless ($address->is_default)
                                <form action="{{ route('profile.addresses.default', $address->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-primary rounded-pill">Đặt mặc định</button>
                                </form>
                                @endunless
                                <button class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-toggle="collapse" data-bs-target="#edit-addr-{{ $address->id }}">Sửa</button>
                                <form action="{{ route('profile.addresses.destroy', $address->id) }}" method="POST" onsubmit="return confirm('Xóa địa chỉ này?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger rounded-pill">Xóa</button>
                                </form>
                            </div>

                            <div class="collapse mt-3" id="edit-addr-{{ $address->id }}">
                                <form action="{{ route('profile.addresses.update', $address->id) }}" method="POST" class="bg-light rounded-3 p-3">
                                    @csrf @method('PUT')
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-6"><input type="text" name="name" value="{{ $address->name }}" class="form-control form-control-sm" placeholder="Tên người nhận" required></div>
                                        <div class="col-md-6"><input type="text" name="phone" value="{{ $address->phone }}" class="form-control form-control-sm" placeholder="Số điện thoại" required></div>
                                        <div class="col-12"><textarea name="address" rows="2" class="form-control form-control-sm" placeholder="Số nhà, tên đường" required>{{ $address->address }}</textarea></div>
                                    </div>

                                    @include('partials._location-picker', [
                                        'codePrefix' => '',
                                        'namePrefix' => '',
                                        'address' => $address,
                                        'idSuffix' => 'profile-' . $address->id,
                                    ])

                                    <button class="btn btn-sm btn-primary rounded-pill mt-2 px-3">Lưu địa chỉ</button>
                                </form>
                            </div>
                        </div>
                        @empty
                        <div class="text-muted text-center py-4">Bạn chưa lưu địa chỉ nào. Thêm một địa chỉ để thanh toán nhanh hơn.</div>
                        @endforelse
                    </div>

                    <div class="col-lg-5">
                        <div class="bg-light rounded-4 p-3">
                            <div class="fw-semibold mb-3">Thêm địa chỉ mới</div>
                            <form action="{{ route('profile.addresses.store') }}" method="POST">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Tên người nhận</label>
                                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Số điện thoại</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Địa chỉ chi tiết (số nhà, tên đường)</label>
                                    <textarea name="address" rows="2" class="form-control" required>{{ old('address') }}</textarea>
                                </div>

                                @include('partials._location-picker', [
                                    'codePrefix' => '',
                                    'namePrefix' => '',
                                    'idSuffix' => 'profile-new',
                                ])

                                <div class="form-text mb-3">
                                    Chọn đủ Tỉnh/Quận/Phường thì lần đặt hàng sau hệ thống tự tính phí ship, không phải nhập lại.
                                </div>

                                <button class="btn btn-primary rounded-pill w-100">Thêm địa chỉ</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== TAB 4: ĐIỂM THƯỞNG & HẠNG THÀNH VIÊN ===== --}}
            <div class="tab-pane fade" id="tab-points">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="bg-light rounded-4 p-3 text-center">
                            <div class="text-muted small">Điểm hiện có</div>
                            <div class="fs-3 fw-bold text-warning">{{ number_format($user->points, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-4 p-3 text-center">
                            <div class="text-muted small">Tổng chi tiêu</div>
                            <div class="fs-5 fw-bold text-danger">{{ number_format($user->lifetime_spent, 0, ',', '.') }} đ</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-4 p-3 text-center">
                            <div class="text-muted small">Hạng thành viên</div>
                            <div class="fs-5 fw-bold">{{ $user->tier_info['icon'] }} {{ $user->tier_label }}</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border rounded-4 small">
                    <div class="fw-semibold mb-2">Cách tích điểm & xếp hạng</div>
                    <ul class="mb-0 ps-3">
                        <li>Mỗi <strong>{{ number_format(\App\Models\User::POINTS_PER_VND, 0, ',', '.') }} đ</strong> giá trị đơn hàng
                            <strong>đã giao thành công</strong> được cộng <strong>1 điểm</strong>.</li>
                        @foreach (array_reverse(\App\Models\User::TIERS, true) as $key => $tier)
                        <li>
                            {{ $tier['icon'] }} <strong>{{ $tier['label'] }}</strong>:
                            chi tiêu từ {{ number_format($tier['min'], 0, ',', '.') }} đ
                            @if ($tier['discount'] > 0) — giảm {{ $tier['discount'] }}% mọi đơn @else — chưa có ưu đãi @endif
                        </li>
                        @endforeach
                    </ul>
                </div>

                <h6 class="fw-bold text-secondary mb-2">Lịch sử điểm thưởng</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Thời gian</th>
                                <th>Nội dung</th>
                                <th class="text-end">Điểm</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pointHistory as $tx)
                            <tr>
                                <td class="text-nowrap small">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $tx->reason }}</td>
                                <td class="text-end fw-bold {{ $tx->points >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->points >= 0 ? '+' : '' }}{{ $tx->points }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center py-4">Chưa có giao dịch điểm nào. Hãy mua sắm để bắt đầu tích điểm!</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@include('partials._location-scripts')
<script>
    // Bộ chọn khu vực nằm trong khối .collapse "Sửa địa chỉ" nên lúc mở trang
    // nó đang ẩn và chưa được khởi tạo. Khởi tạo ngay khi khối được mở ra.
    document.addEventListener('shown.bs.collapse', function (event) {
        if (window.TLLocation) window.TLLocation.init(event.target, true);
    });

    // Tab "Sổ địa chỉ" cũng ẩn lúc đầu -> khởi tạo khi khách bấm sang tab
    document.addEventListener('shown.bs.tab', function () {
        if (window.TLLocation) window.TLLocation.init(document, false);
    });
</script>
@endpush
@endsection
