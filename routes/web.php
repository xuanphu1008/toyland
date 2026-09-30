<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ChildProfileController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MomoController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\User\ChatController as UserChatController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\ChatbotController as AdminChatbotController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\FinanceController as AdminFinanceController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ============ TRANG CÔNG KHAI ============
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/danh-muc', [HomeController::class, 'categories'])->name('categories.index');

// ============ CHATBOT ============
// CỐ Ý đặt NGOÀI middleware auth: khách chưa đăng nhập vẫn hỏi được về sản phẩm,
// phí ship, mã giảm giá. Riêng ý định cần danh tính (tra cứu đơn hàng) thì
// ChatbotService tự kiểm tra và mời khách đăng nhập.
Route::prefix('chatbot')->name('chatbot.')->group(function () {
    Route::get('/chao', [ChatbotController::class, 'greeting'])->name('greeting');
    // throttle: chặn spam - tối đa 30 câu hỏi mỗi phút trên một IP
    Route::post('/gui', [ChatbotController::class, 'send'])->middleware('throttle:30,1')->name('send');
});
Route::get('/san-pham/{product}', [HomeController::class, 'show'])->name('products.show');

// Hỗ trợ khách hàng & nội dung (CMS)
Route::get('/cau-hoi-thuong-gap', [FaqController::class, 'index'])->name('faq.index');
Route::get('/tin-tuc', [PostController::class, 'index'])->name('posts.index');
Route::get('/tin-tuc/{slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/trang/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/ma-giam-gia', [CouponController::class, 'available'])->name('coupons.index');

// ============ XÁC THỰC ============
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Đăng nhập bằng tài khoản Google (OAuth 2.0, không dùng package ngoài)
    Route::get('/auth/google', [SocialAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::get('/register/success', [AuthController::class, 'showRegisterSuccess'])->name('register.success');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Ai đó mở /logout bằng GET (tải lại trang sau khi đăng xuất, bấm Back, hoặc
// Render đánh thức máy chủ rồi tải lại trang) thì dẫn về trang chủ thay vì
// báo "405 Method Not Allowed". KHÔNG đăng xuất qua GET: trang web khác có
// thể nhúng <img src="/logout"> để đá người dùng ra - đăng xuất vẫn chỉ qua POST.
Route::get('/logout', [AuthController::class, 'logoutFallback'])->name('logout.fallback');

// Bấm link xác thực trong email: cho phép Guest (chưa đăng nhập) truy cập, chỉ cần chữ ký hợp lệ
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

// Trang chờ xác thực & gửi lại email (yêu cầu đã đăng nhập)
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerifyNotice'])->name('verification.notice');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Kết quả thanh toán MoMo - BẮT BUỘC nằm NGOÀI middleware auth:
//  - callback: MoMo redirect trình duyệt khách về, session có thể đã mất khi quay lại từ app MoMo.
//  - ipn: MoMo gọi server-to-server, không hề có session/cookie đăng nhập nào.
// Route ipn còn được miễn kiểm tra CSRF trong app/Http/Middleware/VerifyCsrfToken.php.
// Tính an toàn được đảm bảo bằng chữ ký HMAC SHA256 kiểm tra trong MomoService::isValidResponse().
Route::get('/payment/momo/callback', [MomoController::class, 'callback'])->name('payment.momo.callback');
Route::post('/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payment.momo.ipn');

// ============ HỒ SƠ CÁ NHÂN ============
// (chỉ cần đăng nhập, chưa xác thực email vẫn xem/sửa được hồ sơ)
Route::middleware('auth')->prefix('tai-khoan')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/', [ProfileController::class, 'update'])->name('update');
    Route::put('/mat-khau', [ProfileController::class, 'updatePassword'])->name('password');

    // Sổ địa chỉ nhận hàng
    Route::post('/dia-chi', [ProfileController::class, 'storeAddress'])->name('addresses.store');
    Route::put('/dia-chi/{address}', [ProfileController::class, 'updateAddress'])->name('addresses.update');
    Route::patch('/dia-chi/{address}/mac-dinh', [ProfileController::class, 'setDefaultAddress'])->name('addresses.default');
    Route::delete('/dia-chi/{address}', [ProfileController::class, 'destroyAddress'])->name('addresses.destroy');
});

// ============ SO SÁNH SẢN PHẨM ============
// KHÔNG yêu cầu đăng nhập: danh sách so sánh nằm trong session, khách vãng lai
// đang cân nhắc mua hàng cũng phải dùng được.
Route::prefix('so-sanh')->name('compare.')->group(function () {
    Route::get('/', [CompareController::class, 'index'])->name('index');
    Route::post('/{product}', [CompareController::class, 'toggle'])->name('toggle');
    Route::delete('/xoa-het', [CompareController::class, 'clear'])->name('clear');
    Route::delete('/{id}', [CompareController::class, 'remove'])->name('remove');
});

// ============ THÔNG BÁO ============
// Chỉ cần đăng nhập. Admin cũng dùng chung các route này (admin cũng là User).
Route::middleware('auth')->prefix('thong-bao')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    // Số chưa đọc dạng JSON, chuông trên navbar gọi mỗi 60 giây
    Route::get('/chua-doc', [NotificationController::class, 'unreadCount'])->name('unread');
    Route::post('/doc-het', [NotificationController::class, 'markAllRead'])->name('readAll');
    Route::delete('/xoa-het', [NotificationController::class, 'destroyAll'])->name('destroyAll');
    // Đặt SAU các đường dẫn cố định ở trên để "/doc-het" không bị hiểu thành id
    Route::get('/{notification}', [NotificationController::class, 'open'])->name('open');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
});

// ============ HỒ SƠ CỦA BÉ & SỔ SINH NHẬT ============
// Chỉ cần đăng nhập (không bắt xác thực email) - đây là dữ liệu cá nhân hóa,
// không phải thao tác mua bán nên không cần rào chắn nặng như giỏ hàng.
Route::middleware('auth')->prefix('be-yeu')->name('children.')->group(function () {
    Route::get('/', [ChildProfileController::class, 'index'])->name('index');
    Route::get('/so-sinh-nhat', [ChildProfileController::class, 'birthdays'])->name('birthdays');
    Route::get('/them', [ChildProfileController::class, 'create'])->name('create');
    Route::post('/', [ChildProfileController::class, 'store'])->name('store');
    Route::get('/{child}/sua', [ChildProfileController::class, 'edit'])->name('edit');
    Route::put('/{child}', [ChildProfileController::class, 'update'])->name('update');
    Route::delete('/{child}', [ChildProfileController::class, 'destroy'])->name('destroy');

    // Chọn / bỏ chọn bé đang xem (lưu ở session)
    Route::post('/{child}/chon', [ChildProfileController::class, 'select'])->name('select');
    Route::post('/bo-chon', [ChildProfileController::class, 'clearSelection'])->name('clear');
});

// ============ KHÁCH HÀNG ĐÃ ĐĂNG NHẬP + ĐÃ XÁC THỰC EMAIL ============
Route::middleware(['auth', 'verified'])->group(function () {
    // Giỏ hàng
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/buy-now/{product}', [CartController::class, 'buyNow'])->name('cart.buyNow');
    Route::patch('/cart/update/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');

    // Danh sách yêu thích
    Route::get('/yeu-thich', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/yeu-thich/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/yeu-thich/{wishlist}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    // Mã giảm giá áp dụng cho giỏ hàng hiện tại
    Route::post('/ma-giam-gia/ap-dung', [CouponController::class, 'apply'])->name('coupons.apply');
    Route::delete('/ma-giam-gia/go', [CouponController::class, 'remove'])->name('coupons.remove');

    // Đơn hàng
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    // Hóa đơn để in / lưu thành PDF bằng chức năng in sẵn có của trình duyệt
    Route::get('/orders/{order}/hoa-don', [OrderController::class, 'invoice'])->name('orders.invoice');

    // Thanh toán lại bằng MoMo cho đơn chưa thanh toán (nút ở trang lịch sử đơn hàng)
    Route::get('/orders/{order}/momo/pay', [MomoController::class, 'payAgain'])->name('orders.momo.pay');

    // Đánh giá sản phẩm (chỉ sau khi đơn đã giao thành công)
    Route::get('/danh-gia-cua-toi', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/san-pham/{product}/danh-gia', [ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/danh-gia/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Yêu cầu hỗ trợ (ticket)
    Route::get('/ho-tro', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/ho-tro/tao-moi', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/ho-tro', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/ho-tro/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/ho-tro/{ticket}/tra-loi', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::patch('/ho-tro/{ticket}/dong', [TicketController::class, 'close'])->name('tickets.close');

    // Thanh toán
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/', [CheckoutController::class, 'process'])->name('process');
        Route::delete('/addresses/{address}', [CheckoutController::class, 'destroyAddress'])->name('addresses.destroy');
    });

    // AJAX tra cứu Tỉnh/Quận/Phường và tính phí vận chuyển GHN cho form checkout
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/provinces', [OrderController::class, 'getProvinces'])->name('provinces');
        Route::get('/districts', [OrderController::class, 'getDistricts'])->name('districts');
        Route::get('/wards', [OrderController::class, 'getWards'])->name('wards');
        Route::post('/fee', [OrderController::class, 'getShippingFee'])->name('fee');
    });

    // Livechat giữa User và Admin
    Route::post('/user/chat/send', [UserChatController::class, 'send'])->name('user.chat.send');
    Route::get('/user/chat/messages', [UserChatController::class, 'getMessages'])->name('user.chat.messages');
});

// ============ KHU VỰC QUẢN TRỊ ============
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class);
    // Xóa một ảnh phụ khỏi thư viện sản phẩm
    Route::delete('/products/{product}/images/{image}', [ProductController::class, 'destroyImage'])
        ->name('products.images.destroy');
    Route::resource('categories', CategoryController::class);

    // Đơn hàng: chỉ xem danh sách/chi tiết, thao tác đi qua 2 route riêng bên dưới
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show']);
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    // Hóa đơn phía quản trị - dùng chung bản hóa đơn với phía khách
    Route::get('/orders/{order}/hoa-don', [AdminOrderController::class, 'invoice'])->name('orders.invoice');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/charts', [ReportController::class, 'charts'])->name('reports.charts');

    // Tài chính: thống kê thanh toán và tra cứu giao dịch.
    // Đặt route tĩnh /finance/transactions TRƯỚC route có tham số /finance/{order}/status
    // là thói quen an toàn, tránh "transactions" bị hiểu nhầm thành mã đơn hàng.
    Route::get('/finance', [AdminFinanceController::class, 'index'])->name('finance.index');
    Route::get('/finance/transactions', [AdminFinanceController::class, 'transactions'])->name('finance.transactions');
    Route::get('/finance/export', [AdminFinanceController::class, 'export'])->name('finance.export');
    Route::patch('/finance/{order}/status', [AdminFinanceController::class, 'updateStatus'])->name('finance.update-status');

    Route::resource('users', AdminUserController::class);

    // Kiểm duyệt đánh giá sản phẩm
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/toggle', [AdminReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::post('/reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Marketing & khuyến mãi
    Route::resource('coupons', AdminCouponController::class)->except(['show']);

    // Hỗ trợ khách hàng
    Route::resource('faqs', AdminFaqController::class)->except(['show']);

    // Xuất dữ liệu ra Excel (CSV) - đơn hàng, doanh thu, tồn kho, khách hàng
    Route::prefix('xuat-du-lieu')->name('exports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ExportController::class, 'index'])->name('index');
        Route::get('/don-hang', [\App\Http\Controllers\Admin\ExportController::class, 'orders'])->name('orders');
        Route::get('/doanh-thu', [\App\Http\Controllers\Admin\ExportController::class, 'revenue'])->name('revenue');
        Route::get('/ton-kho', [\App\Http\Controllers\Admin\ExportController::class, 'inventory'])->name('inventory');
        Route::get('/khach-hang', [\App\Http\Controllers\Admin\ExportController::class, 'customers'])->name('customers');
    });

    // Sổ sinh nhật khách hàng - danh sách bé sắp sinh nhật để chăm sóc đúng lúc
    Route::get('/so-sinh-nhat', [\App\Http\Controllers\Admin\BirthdayController::class, 'index'])
        ->name('birthdays.index');

    // Theo dõi chatbot: xem lại hội thoại, thống kê ý định, câu bot chưa hiểu
    Route::prefix('chatbot')->name('chatbot.')->group(function () {
        Route::get('/', [AdminChatbotController::class, 'index'])->name('index');
        Route::get('/hoi-thoai/{conversation}', [AdminChatbotController::class, 'show'])->name('show');
        Route::delete('/hoi-thoai/{conversation}', [AdminChatbotController::class, 'destroy'])->name('destroy');
        Route::post('/kiem-tra-ai', [AdminChatbotController::class, 'testLlm'])
            ->middleware('throttle:5,1')
            ->name('test-llm');
    });
    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/tra-loi', [AdminTicketController::class, 'reply'])->name('tickets.reply');
    Route::patch('/tickets/{ticket}/trang-thai', [AdminTicketController::class, 'updateStatus'])->name('tickets.status');

    // Quản lý nội dung (CMS)
    Route::resource('pages', AdminPageController::class)->except(['show']);
    Route::resource('posts', AdminPostController::class)->except(['show']);

    // Livechat giữa User và Admin
    Route::get('/chat/users', [AdminChatController::class, 'getUsers'])->name('chat.users');
    Route::get('/chat/messages/{userId}', [AdminChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/chat/send', [AdminChatController::class, 'send'])->name('chat.send');
});
