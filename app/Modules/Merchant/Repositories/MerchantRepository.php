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
}
