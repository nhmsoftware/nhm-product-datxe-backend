<?php

declare(strict_types=1);

namespace App\Modules\Operation\Repositories;

use App\Modules\Operation\Interfaces\LocationRepositoryInterface;
use App\Modules\Operation\Jobs\SyncLocationToDbJob;
use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\Enums\UserRole;
use Illuminate\Support\Facades\Redis;

/**
 * Repository xử lý việc cập nhật tọa độ vào DB & Redis.
 */
final class LocationRepository implements LocationRepositoryInterface
{
    private const SYNC_LOCK_TTL = 60; // Giây

    /**
     * @inheritDoc
     */
    public function updateDriverLocation(string $userId, float $lat, float $lng): bool
    {
        // 1. Ghi vào Redis (Instant)
        $key = "location:driver:{$userId}";
        Redis::hmset($key, [
            'lat' => $lat,
            'lng' => $lng,
            'updated_at' => now()->toDateTimeString(),
        ]);

        // 2. Thêm vào Geo Set để phục vụ tìm kiếm tài xế gần đây sau này
        Redis::geoadd('locations:drivers:geo', $lng, $lat, (string) $userId);

        // 3. Kiểm tra tiết lưu ghi vào Database
        $this->throttleDbUpdate($userId, UserRole::Driver->value, $lat, $lng);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function updateCustomerLocation(string $userId, float $lat, float $lng): bool
    {
        // 1. Ghi vào Redis (Instant)
        $key = "location:customer:{$userId}";
        Redis::hmset($key, [
            'lat' => $lat,
            'lng' => $lng,
            'updated_at' => now()->toDateTimeString(),
        ]);

        // 2. Kiểm tra tiết lưu ghi vào Database
        $this->throttleDbUpdate($userId, UserRole::Customer->value, $lat, $lng);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getDriverLocation(string $userId): ?array
    {
        $data = Redis::hgetall("location:driver:{$userId}");

        if (!empty($data) && isset($data['lat'], $data['lng'])) {
            return [
                'lat' => (float) $data['lat'],
                'lng' => (float) $data['lng'],
            ];
        }

        // Fallback to DB
        $profile = DriverProfile::where('user_id', $userId)->first();
        if ($profile && $profile->current_lat) {
            return [
                'lat' => (float) $profile->current_lat,
                'lng' => (float) $profile->current_lng,
            ];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getCustomerLocation(string $userId): ?array
    {
        $data = Redis::hgetall("location:customer:{$userId}");

        if (!empty($data) && isset($data['lat'], $data['lng'])) {
            return [
                'lat' => (float) $data['lat'],
                'lng' => (float) $data['lng'],
            ];
        }

        // Fallback to DB
        $profile = CustomerProfile::where('user_id', $userId)->first();
        if ($profile && $profile->current_lat) {
            return [
                'lat' => (float) $profile->current_lat,
                'lng' => (float) $profile->current_lng,
            ];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function findNearbyDriverIds(float $lat, float $lng, float $radiusKm): array
    {
        return Redis::georadius('locations:drivers:geo', $lng, $lat, $radiusKm, 'km') ?: [];
    }

    /**
     * Helper kiểm tra và thực hiện đồng bộ DB có tiết lưu (Throttling).
     */
    private function throttleDbUpdate(string $userId, int $role, float $lat, float $lng): void
    {
        $lockKey = "location:sync_lock:{$userId}";

        if (!Redis::get($lockKey)) {
            // Đẩy Job ghi vào DB bất đồng bộ
            SyncLocationToDbJob::dispatch($userId, $role, $lat, $lng);

            // Tạo lock với TTL 60s
            Redis::setex($lockKey, self::SYNC_LOCK_TTL, '1');
        }
    }
}
