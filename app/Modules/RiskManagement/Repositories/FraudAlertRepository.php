<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\RiskManagement\Interfaces\FraudAlertRepositoryInterface;
use App\Modules\RiskManagement\Model\Enums\FraudAlertStatus;
use App\Modules\RiskManagement\Model\FraudAlert;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repository quản lý dữ liệu FraudAlert.
 */
final class FraudAlertRepository extends BaseRepository implements FraudAlertRepositoryInterface
{
    public function getModel(): string
    {
        return FraudAlert::class;
    }

    /**
     * @inheritDoc
     */
    public function listAlerts(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->getQuery();

        if (!empty($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        if (!empty($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['fraud_type'])) {
            $query->where('fraud_type', $filters['fraud_type']);
        }

        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['keyword'] . '%')
                  ->orWhere('target_id', 'like', '%' . $filters['keyword'] . '%');
            });
        }

        return $query->orderBy('detected_at', 'desc')->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function getFraudStatistics(): array
    {
        $riskLevelSummary = $this->model->select('risk_level', DB::raw('count(*) as total'))
            ->groupBy('risk_level')
            ->get()
            ->mapWithKeys(function ($item) {
                // Đảm bảo dùng value (int) làm key để tránh lỗi Enum offset
                $key = is_object($item->risk_level) ? $item->risk_level->value : $item->risk_level;
                return [(string)$key => $item->total];
            })->toArray();

        $targetTypeSummary = $this->model->select('target_type', DB::raw('count(*) as total'))
            ->groupBy('target_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $key = is_object($item->target_type) ? $item->target_type->value : $item->target_type;
                return [(string)$key => $item->total];
            })->toArray();

        return [
            'active_alerts_count' => $this->model->whereIn('status', [
                FraudAlertStatus::PENDING->value,
                FraudAlertStatus::INVESTIGATING->value
            ])->count(),
            
            'risk_level_summary' => $riskLevelSummary,
            'target_type_summary' => $targetTypeSummary,
        ];
    }
}
