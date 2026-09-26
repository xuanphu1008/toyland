<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

// Wrapper gọi các API công khai của Giao Hàng Nhanh (GHN) - https://api.ghn.vn
class GHNService
{
    protected string $baseUrl;
    protected string $token;
    protected string $shopId;
    protected bool $verifySsl;
    protected ?int $defaultFromDistrictId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ghn.base_url'), '/');
        $this->token = (string) config('services.ghn.token');
        $this->shopId = (string) config('services.ghn.shop_id');
        $this->verifySsl = (bool) config('services.ghn.verify_ssl', true);
        $this->defaultFromDistrictId = config('services.ghn.from_district_id')
            ? (int) config('services.ghn.from_district_id')
            : null;
    }

    // HTTP client dùng chung cho mọi request tới GHN: gắn sẵn Token/ShopId header
    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Token' => $this->token,
                'ShopId' => $this->shopId,
            ])
            ->withOptions(['verify' => $this->verifySsl])
            ->acceptJson()
            ->timeout(15);
    }

    public function getProvinces(): array
    {
        $response = $this->client()->get('/master-data/province');

        return $this->handle($response);
    }

    public function getDistricts(int $provinceId): array
    {
        $response = $this->client()->get('/master-data/district', [
            'province_id' => $provinceId,
        ]);

        return $this->handle($response);
    }

    public function getWards(int $districtId): array
    {
        $response = $this->client()->get('/master-data/ward', [
            'district_id' => $districtId,
        ]);

        return $this->handle($response);
    }

    // $payload: to_district_id, to_ward_code, weight, (tùy chọn) length/width/height, service_type_id...
    public function calculateFee(array $payload): array
    {
        $payload = array_merge([
            'from_district_id' => $this->defaultFromDistrictId,
            'service_type_id' => 2,
        ], $payload);

        $response = $this->client()->post('/v2/shipping-order/fee', $payload);

        return $this->handle($response);
    }

    // $payload xem App\Services\GHNOrderService::buildOrderPayload()
    public function createOrder(array $payload): array
    {
        $payload = array_merge([
            'from_district_id' => $this->defaultFromDistrictId,
        ], $payload);

        $response = $this->client()->post('/v2/shipping-order/create', $payload);

        return $this->handle($response);
    }

    public function cancelOrder(string $orderCode): array
    {
        $response = $this->client()->post('/v2/switch-status/cancel', [
            'order_codes' => [$orderCode],
        ]);

        return $this->handle($response);
    }

    protected function handle(Response $response): array
    {
        if ($response->failed()) {
            Log::error('GHN API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                $response->json('message') ?: 'Không thể kết nối tới dịch vụ vận chuyển GHN.'
            );
        }

        return (array) $response->json('data', []);
    }
}
