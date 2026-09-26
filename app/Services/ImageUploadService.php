<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Lưu ảnh tải lên vào thư mục public/uploads.
 *
 * VÌ SAO KHÔNG DÙNG Storage::disk('public')?
 * Cách cũ lưu file vào storage/app/public rồi phải chạy `php artisan storage:link`
 * để tạo symbolic link sang public/storage. Trên Windows, tạo symlink đòi quyền
 * Administrator (hoặc bật Developer Mode), nên rất hay gặp cảnh "upload thành công
 * mà ảnh vẫn hiện icon vỡ". Ghi thẳng vào public/uploads thì không cần symlink,
 * chạy giống nhau trên mọi máy.
 *
 * KHI CHẠY TRÊN RENDER: ổ đĩa của máy chủ bị xóa mỗi lần khởi động lại, nên nếu
 * đã khai Cloudinary (config/services.php) thì ảnh được đẩy lên Cloudinary và
 * DB lưu thẳng URL https đầy đủ. Các accessor image_url/url của model vốn đã
 * trả nguyên URL http(s), nên giao diện không phải sửa gì.
 */
class ImageUploadService
{
    /** Đuôi file được chấp nhận */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif'];

    /** Dung lượng tối đa (KB) */
    public const MAX_KILOBYTES = 5120;

    /**
     * Lưu ảnh và trả về giá trị để ghi vào DB:
     *  - Có Cloudinary: URL đầy đủ, ví dụ "https://res.cloudinary.com/<cloud>/image/upload/v1/toyland/products/abc.jpg"
     *  - Không có:      đường dẫn tương đối, ví dụ "uploads/products/abc123.jpg"
     */
    public function store(UploadedFile $file, string $folder): string
    {
        $folder = trim($folder, '/');

        if ($this->usesCloudinary()) {
            return $this->storeOnCloudinary($file, $folder);
        }

        $directory = public_path('uploads/' . $folder);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'jpg';
        }

        // Tên file: slug từ tên gốc + chuỗi ngẫu nhiên, tránh trùng và tránh ký tự lạ
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'anh';
        $fileName = Str::limit($base, 40, '') . '-' . Str::lower(Str::random(8)) . '.' . $extension;

        $file->move($directory, $fileName);

        return 'uploads/' . $folder . '/' . $fileName;
    }

    /**
     * Xóa ảnh cũ. Hỗ trợ cả đường dẫn kiểu mới (uploads/...) lẫn kiểu cũ (products/...).
     */
    public function delete(?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '') {
            return;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            // Chỉ xóa ảnh do chính tài khoản Cloudinary của mình quản lý;
            // URL ảnh ngoài (dán link từ web khác) thì để nguyên.
            $publicId = $this->cloudinaryPublicId($path);

            if ($publicId !== null && $this->usesCloudinary()) {
                $this->destroyOnCloudinary($publicId);
            }

            return;
        }

        if (Str::startsWith($path, 'uploads/')) {
            $full = public_path($path);

            if (is_file($full)) {
                @unlink($full);
            }

            return;
        }

        // Ảnh cũ nằm trong disk 'public'
        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            // Ảnh không còn thì thôi, không để việc xóa ảnh làm hỏng cả thao tác lưu
        }
    }

    /* ==================================================================
       CLOUDINARY - gọi thẳng REST API bằng Http facade, không cần cài thêm
       gói composer nào (nên composer.lock trên máy bạn giữ nguyên).
       ================================================================== */

    /** Đã khai đủ thông tin Cloudinary chưa */
    public function usesCloudinary(): bool
    {
        $c = $this->cloudinaryCredentials();

        return $c['cloud_name'] !== '' && $c['api_key'] !== '' && $c['api_secret'] !== '';
    }

    /**
     * Gom thông tin đăng nhập: ưu tiên 3 biến rời, thiếu biến nào thì lấy từ
     * CLOUDINARY_URL dạng cloudinary://<api_key>:<api_secret>@<cloud_name>.
     */
    public function cloudinaryCredentials(): array
    {
        $config = (array) config('services.cloudinary', []);
        $fromUrl = ['cloud_name' => '', 'api_key' => '', 'api_secret' => ''];

        $url = trim((string) ($config['url'] ?? ''));

        if ($url !== '' && Str::startsWith($url, 'cloudinary://')) {
            $parts = parse_url($url);

            $fromUrl = [
                'cloud_name' => (string) ($parts['host'] ?? ''),
                'api_key' => rawurldecode((string) ($parts['user'] ?? '')),
                'api_secret' => rawurldecode((string) ($parts['pass'] ?? '')),
            ];
        }

        $pick = function (string $key) use ($config, $fromUrl): string {
            $value = trim((string) ($config[$key] ?? ''));

            return $value !== '' ? $value : $fromUrl[$key];
        };

        return [
            'cloud_name' => $pick('cloud_name'),
            'api_key' => $pick('api_key'),
            'api_secret' => $pick('api_secret'),
        ];
    }

    /**
     * Chữ ký theo quy định của Cloudinary: sắp xếp tham số theo tên (a-z),
     * nối thành "a=1&b=2", dán api_secret vào cuối rồi băm SHA-1.
     * Không đưa file, api_key, cloud_name, resource_type vào chữ ký.
     */
    public static function cloudinarySignature(array $params, string $apiSecret): string
    {
        unset($params['file'], $params['api_key'], $params['cloud_name'], $params['resource_type'], $params['signature']);

        $params = array_filter($params, fn ($v) => $v !== null && $v !== '');
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . (is_array($value) ? implode(',', $value) : $value);
        }

        return sha1(implode('&', $pairs) . $apiSecret);
    }

    /**
     * Lấy public_id từ URL Cloudinary của CHÍNH tài khoản đang cấu hình.
     * https://res.cloudinary.com/<cloud>/image/upload/[biến-đổi/]v123/toyland/products/abc.jpg
     *   -> "toyland/products/abc"
     * Trả null nếu URL không thuộc tài khoản này.
     */
    public function cloudinaryPublicId(string $url): ?string
    {
        $cloud = $this->cloudinaryCredentials()['cloud_name'];

        if ($cloud === '') {
            return null;
        }

        $pattern = '#^https?://res\.cloudinary\.com/' . preg_quote($cloud, '#')
            . '/image/upload/(?:.*?/)?v\d+/([^?\#]+?)(?:\.[a-z0-9]{2,5})?(?:[?\#].*)?$#i';

        if (! preg_match($pattern, $url, $m)) {
            return null;
        }

        return rawurldecode($m[1]);
    }

    private function http()
    {
        $config = (array) config('services.cloudinary', []);

        return Http::timeout((int) ($config['timeout'] ?? 30) ?: 30)
            ->withOptions(['verify' => filter_var($config['verify_ssl'] ?? true, FILTER_VALIDATE_BOOLEAN)]);
    }

    private function storeOnCloudinary(UploadedFile $file, string $folder): string
    {
        $c = $this->cloudinaryCredentials();
        $config = (array) config('services.cloudinary', []);

        $root = trim((string) ($config['folder'] ?? 'toyland'), '/');
        $max = (int) ($config['max_dimension'] ?? 1600);

        $params = [
            'folder' => trim($root . '/' . $folder, '/'),
            'timestamp' => time(),
            // Ảnh chụp điện thoại 4000px là thừa với web: thu về tối đa $max px,
            // giữ tỉ lệ, ảnh nhỏ hơn thì để nguyên (c_limit).
            'transformation' => $max > 0 ? "c_limit,w_{$max},h_{$max}" : null,
        ];
        $params = array_filter($params, fn ($v) => $v !== null);
        $params['signature'] = self::cloudinarySignature($params, $c['api_secret']);
        $params['api_key'] = $c['api_key'];

        $endpoint = 'https://api.cloudinary.com/v1_1/' . rawurlencode($c['cloud_name']) . '/image/upload';

        try {
            // Ảnh tối đa 5MB nên đọc thẳng vào bộ nhớ là ổn, khỏi lo đóng/mở file
            $response = $this->http()
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName() ?: 'anh.jpg')
                ->post($endpoint, $params);
        } catch (\Throwable $e) {
            Log::error('Cloudinary upload lỗi kết nối: ' . $e->getMessage());

            $this->fail('Không kết nối được tới máy chủ lưu ảnh (Cloudinary). Vui lòng thử lại sau ít phút.');
        }

        $url = $response->json('secure_url');

        if (! $response->successful() || ! is_string($url) || $url === '') {
            // Cloudinary trả lỗi dạng {"error":{"message":"..."}}; ghi log để tra, không lộ cho người dùng
            Log::error('Cloudinary upload thất bại', [
                'status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);

            $this->fail('Máy chủ lưu ảnh từ chối ảnh này (mã ' . $response->status() . '). '
                . 'Kiểm tra lại cấu hình Cloudinary hoặc thử ảnh khác.');
        }

        return $url;
    }

    private function destroyOnCloudinary(string $publicId): void
    {
        $c = $this->cloudinaryCredentials();

        $params = [
            'public_id' => $publicId,
            'timestamp' => time(),
            // Xóa luôn bản đang lưu đệm trên CDN, không thì ảnh cũ còn hiện thêm vài ngày
            'invalidate' => 'true',
        ];
        $params['signature'] = self::cloudinarySignature($params, $c['api_secret']);
        $params['api_key'] = $c['api_key'];

        $endpoint = 'https://api.cloudinary.com/v1_1/' . rawurlencode($c['cloud_name']) . '/image/destroy';

        try {
            $response = $this->http()->asForm()->post($endpoint, $params);

            if (! $response->successful()) {
                Log::warning('Cloudinary xóa ảnh thất bại', ['public_id' => $publicId, 'status' => $response->status()]);
            }
        } catch (\Throwable $e) {
            // Xóa ảnh cũ chỉ là dọn dẹp - lỗi thì ghi log, không làm hỏng thao tác chính
            Log::warning('Cloudinary xóa ảnh lỗi kết nối: ' . $e->getMessage(), ['public_id' => $publicId]);
        }
    }

    /**
     * Báo lỗi về lại form (giống lỗi validate) thay vì trang 500:
     * người quản trị thấy ngay thông báo và dữ liệu đã nhập vẫn còn nguyên.
     */
    private function fail(string $message): void
    {
        throw ValidationException::withMessages(['image' => $message]);
    }

    /**
     * Bộ quy tắc validate ảnh.
     *
     * CỐ Ý KHÔNG DÙNG rule 'image'/'mimes' của Laravel: hai rule đó đoán kiểu file
     * bằng extension `fileinfo` của PHP. Nếu Laragon/XAMPP chưa bật extension=fileinfo
     * thì MỌI định dạng đều bị báo "không phải hình ảnh" - đúng triệu chứng
     * "thêm ảnh nào cũng lỗi". Ở đây tự kiểm bằng đuôi file + getimagesize()
     * (hàm lõi của PHP, luôn có sẵn) nên không phụ thuộc cấu hình máy.
     */
    public static function validationRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'max:' . self::MAX_KILOBYTES,
            function ($attribute, $value, $fail) {
                if (! $value instanceof UploadedFile) {
                    return;
                }

                $extension = strtolower($value->getClientOriginalExtension());

                if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    $fail('Chỉ nhận ảnh có đuôi: ' . implode(', ', self::ALLOWED_EXTENSIONS) . '.');

                    return;
                }

                if (@getimagesize($value->getRealPath()) === false) {
                    $fail('File tải lên không phải là ảnh hợp lệ (hoặc đã bị hỏng).');
                }
            },
        ];
    }

    /**
     * Thông báo lỗi tiếng Việt kèm gợi ý xử lý.
     */
    public static function validationMessages(string $field = 'image'): array
    {
        return [
            $field . '.max' => 'Ảnh vượt quá ' . (self::MAX_KILOBYTES / 1024) . 'MB. Hãy chọn ảnh nhẹ hơn.',
            $field . '.file' => 'Không nhận được file ảnh. Nếu ảnh quá nặng, hãy tăng upload_max_filesize '
                . 'và post_max_size trong php.ini rồi khởi động lại server.',
        ];
    }
}
