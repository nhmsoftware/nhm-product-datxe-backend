<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\RiskManagement\Interfaces\CancellationConfigRepositoryInterface;
use App\Modules\RiskManagement\Model\ScheduledRideCancellationConfig;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CancellationConfigRepository extends BaseRepository implements CancellationConfigRepositoryInterface
{
    public function getModel(): string
    {
        return ScheduledRideCancellationConfig::class;
    }

    /**
     * @inheritDoc
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->getQuery();

        if (isset($filters['ride_type'])) {
            $query->where('ride_type', $filters['ride_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('min_minutes_before_pickup', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * @inheritDoc
     */
    public function findApplicableRule(int $rideType, int $minutesUntilPickup): ?ScheduledRideCancellationConfig
    {
        /** @var ScheduledRideCancellationConfig|null */
        return $this->getQuery()
            ->where('ride_type', $rideType)
            ->where('is_active', true)
            // Lấy quy tắc có min_minutes_before_pickup lớn nhất nhưng vẫn <= minutesUntilPickup
            // Ví dụ: rules: [120 min: fee 0], [60 min: fee 50%], [0 min: fee 100%]
            // Nếu minutesUntilPickup = 90, sẽ lấy rule [60 min].
            // Nếu minutesUntilPickup = 150, sẽ lấy rule [120 min].
            ->where('min_minutes_before_pickup', '<=', $minutesUntilPickup)
            ->orderBy('min_minutes_before_pickup', 'desc')
            ->first();
    }
}
