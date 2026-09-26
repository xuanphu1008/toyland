<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ChildProfile;
use App\Models\Page;
use App\Models\UserNotification;
use App\Models\Wishlist;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Dùng lệnh này là chuẩn nhất cho mọi phiên bản Laravel
        Paginator::useBootstrap();

        // Trên Render: ép link sinh ra (kể cả link trong email gửi từ lệnh
        // artisan, lúc không có request) đều là https.
        if (filter_var(config('toyland.force_https'), FILTER_VALIDATE_BOOLEAN)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        /**
         * Chia sẻ dữ liệu dùng chung cho mọi layout:
         *  - $footerPages: các trang tĩnh hiển thị ở chân trang (CMS)
         *  - $wishlistCount: số sản phẩm trong danh sách yêu thích của khách
         *  - $navCategories: cây danh mục 2 cấp đổ vào menu "Danh mục" trên navbar
         *    (thay cho trang /danh-muc đã bỏ)
         *
         * Bọc try/catch và kiểm tra bảng tồn tại để lệnh `php artisan migrate`
         * lần đầu (khi các bảng này chưa có) không bị lỗi.
         */
        View::composer(['layouts.user', 'layout'], function ($view) {
            $footerPages = collect();
            $wishlistCount = 0;
            $navCategories = collect();

            try {
                if (Schema::hasTable('categories')) {
                    $navCategories = Category::tree();
                }

                if (Schema::hasTable('pages')) {
                    $footerPages = Page::published()
                        ->where('show_in_footer', true)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get(['slug', 'title']);
                }

                if (Auth::check() && Schema::hasTable('wishlists')) {
                    $wishlistCount = Wishlist::where('user_id', Auth::id())->count();
                }
            } catch (Throwable $e) {
                // Chưa migrate xong thì bỏ qua, giao diện vẫn hiển thị bình thường
            }

            $view->with('footerPages', $footerPages)
                ->with('wishlistCount', $wishlistCount)
                ->with('navCategories', $navCategories);
        });

        /**
         * Dữ liệu cá nhân của khách (hồ sơ bé + thông báo) - chia sẻ cho MỌI view.
         *
         * VÌ SAO PHẢI LÀ '*':
         * Blade render view con TRƯỚC rồi mới render layout. Composer gắn vào
         * 'layouts.user' chỉ bơm biến vào đúng file layout đó, nên view con
         * (children/index, welcome...) chạy trước và không hề thấy $activeChild
         * -> "Undefined variable". Gắn vào '*' thì view nào cũng có.
         *
         * VÌ SAO PHẢI NHỚ KẾT QUẢ (memoize):
         * Composer '*' chạy lại ở MỖI view và MỖI @include - một trang có chục
         * partial là chục lần. Biến static giữ kết quả nên truy vấn CSDL chỉ
         * chạy đúng một lần cho cả request.
         */
        View::composer('*', function ($view) {
            static $context = null;

            if ($context === null) {
                $context = $this->userContext();
            }

            $view->with($context);
        });
    }

    /**
     * Dựng dữ liệu cá nhân của khách cho request hiện tại:
     * hồ sơ các bé, bé đang chọn, bé sắp sinh nhật, và thông báo chưa đọc.
     */
    private function userContext(): array
    {
        $children = collect();
        $active = null;
        $soon = collect();
        $notifications = collect();
        $unreadNotifications = 0;

        // Danh sách so sánh nằm hoàn toàn trong session nên đọc được kể cả khi
        // chưa đăng nhập và kể cả khi CSDL đang có vấn đề.
        $compareIds = array_values(array_filter(array_map('intval', (array) session('compare', []))));

        try {
            if (Auth::check() && Schema::hasTable('child_profiles')) {
                $children = ChildProfile::where('user_id', Auth::id())
                    ->orderBy('birthday')
                    ->get();

                // Bé đang được chọn. Lọc trong tập ĐÃ TẢI (vốn đã lọc theo
                // user_id) chứ không query lại theo id trong session, nên
                // session bị sửa tay cũng chỉ ra null chứ không lấy được
                // hồ sơ con nhà người khác.
                $active = $children->firstWhere('id', (int) session('active_child_id'));

                // Sinh nhật phải tính ở PHP: so sánh "ngày/tháng bất kể năm"
                // mỗi hệ CSDL viết một kiểu, mà mỗi tài khoản chỉ có vài bé.
                $soon = $children->filter->is_birthday_soon
                    ->sortBy('days_until_birthday')
                    ->values();
            }

            if (Auth::check() && Schema::hasTable('user_notifications')) {
                // Chỉ lấy 8 cái mới nhất cho danh sách xổ xuống ở chuông;
                // xem đầy đủ thì sang trang /thong-bao.
                $notifications = UserNotification::forUser(Auth::id())
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get();

                // Đếm riêng chứ không đếm trong 8 cái trên: khách có thể có
                // 30 thông báo chưa đọc, con số trên chuông phải là 30.
                $unreadNotifications = UserNotification::forUser(Auth::id())->unread()->count();
            }
        } catch (Throwable $e) {
            // Chưa migrate xong thì bỏ qua, giao diện vẫn chạy bình thường
        }

        return [
            'myChildren' => $children,
            'activeChild' => $active,
            'birthdaySoon' => $soon,
            'myNotifications' => $notifications,
            'unreadNotifications' => $unreadNotifications,
            'compareIds' => $compareIds,
            'compareCount' => count($compareIds),
        ];
    }
}
