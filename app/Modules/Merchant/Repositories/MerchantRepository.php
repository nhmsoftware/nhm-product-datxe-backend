<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\User\Model\MerchantProfile;
use Illuminate\Pagination\LengthAwarePaginator;

final class MerchantRepository extends BaseRepository implements MerchantRepositoryInterface
{
    public function getModel(): string
    {
        return MerchantProfile::class;
    }

    public function findByUserId(string $userId): ?MerchantProfile
    {
        /** @var MerchantProfile|null */
        return $this->getQuery()->where('user_id', $userId)->first();
    }

    public function isStoreNameExists(string $storeName, ?string $excludeUserId = null): bool
    {
        $query = $this->getQuery()->where('store_name', $storeName);
        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }
        return $query->exists();
    }

    public function updateOpeningHoursSchedule(string $merchantProfileId, array $schedule): bool
    {
        /** @var MerchantProfile|null $profile */
        $profile = $this->getQuery()->find($merchantProfileId);
        if (!$profile) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($profile, $schedule) {
            foreach ($schedule as $item) {
                $profile->openingHours()->updateOrCreate(
                    [
                        'day_of_week' => $item['day_of_week'],
                    ],
                    [
                        'opening_time' => $item['opening_time'] ?? null,
                        'closing_time' => $item['closing_time'] ?? null,
                        'is_closed'    => $item['is_closed'] ?? false,
                        'is_overnight' => $item['is_overnight'] ?? false,
                    ]
                );
            }
            return true;
        });
    }

    public function searchMerchants(\App\Modules\Merchant\DTO\MerchantFilterDTO $dto): LengthAwarePaginator
    {
        $query = $this->getQuery()->with(['user', 'user.customerProfile']);

        if ($dto->keyword) {
            $query->where(function ($q) use ($dto) {
                $q->where('store_name', 'like', '%' . $dto->keyword . '%')
                    ->orWhereHas('user', function ($uq) use ($dto) {
                        $uq->where('phone', 'like', '%' . $dto->keyword . '%')
                            ->orWhere('email', 'like', '%' . $dto->keyword . '%')
                            ->orWhereHas('customerProfile', function ($sq) use ($dto) {
                                $sq->where('full_name', 'like', '%' . $dto->keyword . '%');
                            });
                    });
            });
        }

        if ($dto->storeName) {
            $query->where('store_name', 'like', '%' . $dto->storeName . '%');
        }

        if ($dto->status !== null) {
            $query->where('status', $dto->status);
        }

        if ($dto->ownerName || $dto->phone || $dto->email || $dto->isActive !== null) {
            $query->whereHas('user', function ($q) use ($dto) {
                if ($dto->ownerName) {
                    $q->whereHas('customerProfile', function ($sq) use ($dto) {
                        $sq->where('full_name', 'like', '%' . $dto->ownerName . '%');
                    });
                }
                if ($dto->phone) {
                    $q->where('phone', 'like', '%' . $dto->phone . '%');
                }
                if ($dto->email) {
                    $q->where('email', 'like', '%' . $dto->email . '%');
                }
                if ($dto->isActive !== null) {
                    $q->where('is_active', $dto->isActive);
                }
            });
        }

        return $query->latest()->paginate($dto->limit, ['*'], 'page', $dto->page);
    }

    public function getNearbyMerchants(\App\Modules\Merchant\DTO\GetNearbyMerchantsDTO $dto): LengthAwarePaginator
    {
        $latitude = $dto->latitude;
        $longitude = $dto->longitude;
        $radius = $dto->radiusInKm;

        // Sử dụng công thức Haversine an toàn với least/greatest tránh sai số dấu phẩy động vượt quá [-1, 1] cho acos
        // Đồng thời CAST sang REAL để gán kiểu số (affinity) cho SQLite/MySQL/PostgreSQL, tránh lỗi so sánh với chuỗi tham số
        $haversineSql = "CAST((6371 * acos(least(greatest(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)), -1.0), 1.0))) AS REAL)";

        $query = $this->getQuery()
            ->selectRaw("*, {$haversineSql} AS distance", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', \App\Modules\User\Model\Enums\KycStatus::Approved)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->whereRaw("{$haversineSql} <= ?", [$latitude, $longitude, $latitude, $radius])
            ->with(['user', 'openingHours']);

        if ($dto->keyword) {
            $query->where('store_name', 'like', '%' . $dto->keyword . '%');
        }

        return $query->orderBy('distance')
            ->paginate($dto->limit, ['*'], 'page', $dto->page);
    }

    public function getByIdForCustomer(string $id): ?\App\Modules\User\Model\MerchantProfile
    {
        return $this->getQuery()
            ->with(['openingHours'])
            ->where('id', $id)
            ->where('status', \App\Modules\User\Model\Enums\KycStatus::Approved)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->first();
    }

    public function updateRatingStats(string $merchantProfileId, float $averageRating, int $totalOrders): bool
    {
        return (bool) $this->getQuery()->where('id', $merchantProfileId)->update([
            'average_rating' => $averageRating,
            'total_orders'   => $totalOrders,
        ]);
    }
}
