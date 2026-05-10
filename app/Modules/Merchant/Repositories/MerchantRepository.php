<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\User\Model\MerchantProfile;
use App\Modules\User\Model\User;

final class MerchantRepository extends BaseRepository implements MerchantRepositoryInterface
{
    public function getModel(): string
    {
        return MerchantProfile::class;
    }

    public function findByUserId(string $userId): ?MerchantProfile
    {
        /** @var MerchantProfile|null */
        return $this->model->where('user_id', $userId)->first();
    }

    public function isCitizenIdExists(string $citizenId, ?string $excludeUserId = null): bool
    {
        $query = User::where('citizen_id', $citizenId);
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        return $query->exists();
    }

    public function isStoreNameExists(string $storeName, ?string $excludeUserId = null): bool
    {
        $query = $this->model->where('store_name', $storeName);
        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }
        return $query->exists();
    }

    public function updateOpeningHoursSchedule(string $merchantProfileId, array $schedule): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($merchantProfileId, $schedule) {
            foreach ($schedule as $item) {
                \App\Modules\User\Model\MerchantOpeningHour::updateOrCreate(
                    [
                        'merchant_profile_id' => $merchantProfileId,
                        'day_of_week'         => $item['day_of_week'],
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
    public function searchMerchants(\App\Modules\Merchant\DTO\MerchantFilterDTO $dto): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with(['user']);

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

    public function updateRatingStats(string $merchantProfileId): bool
    {
        $stats = DB::table('food_ratings')
            ->where('merchant_id', $merchantProfileId)
            ->select([
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('AVG(rating) as average_rating')
            ])
            ->first();

        return (bool) $this->model->where('id', $merchantProfileId)->update([
            'average_rating' => $stats->average_rating ?? 0,
            'total_orders'   => $stats->total_orders ?? 0,
        ]);
    }
}
