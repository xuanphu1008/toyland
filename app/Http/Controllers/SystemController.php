<?php

namespace App\Http\Controllers;

use App\Console\Kernel as ConsoleKernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hai đường dẫn phục vụ việc vận hành trên Render (khai trong routes/api.php
 * để KHÔNG đi qua session/CSRF: health check gọi vài giây một lần, nếu đi qua
 * session thì mỗi lần gọi lại đẻ thêm một dòng trong bảng sessions).
 */
class SystemController extends Controller
{
    /**
     * GET /api/healthz - Render gọi để biết container đã sẵn sàng nhận khách.
     * Cố ý không chạm CSDL: MySQL chậm một nhịp không có nghĩa web đã chết,
     * và không muốn Render khởi động lại container chỉ vì Aiven đang thức dậy.
     */
    public function health(): Response
    {
        return response('ok', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * GET /api/cron/daily - thay cho cron của máy chủ (Render gói miễn phí không có).
     *
     * Một dịch vụ hẹn giờ bên ngoài (cron-job.org) gọi vào mỗi sáng, kèm header
     *   Authorization: Bearer <CRON_TOKEN>
     * Mã đặt ở header chứ không đặt trên URL để không bị in ra nhật ký truy cập.
     *
     * Mỗi lệnh chỉ chạy MỘT LẦN MỖI NGÀY dù bị gọi bao nhiêu lần (cron-job.org
     * gọi lại khi lần trước quá thời gian chờ lúc máy chủ đang ngủ dậy): khóa
     * theo ngày lưu trong bảng cache của MySQL nên còn nguyên qua các lần khởi động lại.
     */
    public function daily(Request $request): JsonResponse
    {
        $expected = (string) config('toyland.cron_token');

        // Chưa khai mã = tính năng tắt; trả 404 như thể đường dẫn không tồn tại
        if ($expected === '') {
            abort(404);
        }

        // Nhận cả 3 kiểu khai: "Authorization: Bearer <mã>", "Authorization: <mã>"
        // (quên chữ Bearer - lỗi rất hay gặp khi điền trên cron-job.org) và "X-Cron-Token: <mã>"
        $given = (string) ($request->bearerToken()
            ?: $request->header('X-Cron-Token')
            ?: $request->header('Authorization', ''));
        $given = trim($given);

        if ($given === '' || ! hash_equals($expected, $given)) {
            abort(403);
        }

        @set_time_limit(300);

        $today = now()->toDateString();
        $result = [];

        foreach (ConsoleKernel::DAILY_COMMANDS as $command) {
            $key = 'cron:daily:' . $command . ':' . $today;

            // add() chỉ ghi được khi khóa chưa có -> nguyên tử, hai lượt gọi
            // chồng nhau thì chỉ một lượt được chạy.
            if (! Cache::add($key, now()->toDateTimeString(), now()->addDays(2))) {
                $result[$command] = 'đã chạy hôm nay';

                continue;
            }

            try {
                $exitCode = Artisan::call($command);
                $result[$command] = $exitCode === 0 ? 'xong' : 'lỗi (mã ' . $exitCode . ')';
            } catch (Throwable $e) {
                // Nhả khóa để lần gọi sau thử lại
                Cache::forget($key);
                Log::error('Việc hằng ngày ' . $command . ' lỗi: ' . $e->getMessage());
                $result[$command] = 'lỗi, sẽ thử lại ở lần gọi sau';
            }
        }

        return response()->json(['date' => $today, 'tasks' => $result]);
    }
}
