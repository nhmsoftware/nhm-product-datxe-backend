<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\CommissionRuleRepositoryInterface;
use App\Modules\Finance\Model\CommissionRule;
use App\Modules\Finance\Model\Enums\CommissionScope;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use Illuminate\Database\Eloquent\Collection;

final class CommissionRuleRepository extends BaseRepository implements CommissionRuleRepositoryInterface
{
    public function getModel(): string
    {
        return CommissionRule::class;
    }

    public function getActiveRule(
        \App\Modules\Finance\Model\Enums\CommissionTargetType $targetType,
        CommissionServiceType $serviceType,
        ?string               $areaId = null
    ): ?CommissionRule {
        $now = now();
        $query = $this->model->where('target_type', $targetType->value)
            ->where('service_type', $serviceType->value)
            ->where('is_active', true)
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $now);
            });

        // Ưu tiên regional rule trước
        if ($areaId) {
            $regionalRule = (clone $query)->where('scope', CommissionScope::REGIONAL->value)
                ->where('area_id', $areaId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($regionalRule) {
                return $regionalRule;
            }
        }

        // Nếu không có regional rule thì lấy system rule
        return $query->where('scope', CommissionScope::SYSTEM->value)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function getAllRules(): Collection
    {
        return $this->model->orderBy('created_at', 'desc')->get();
    }

    public function hasOverlappingRule(
        \App\Modules\Finance\Model\Enums\CommissionTargetType $targetType,
        CommissionServiceType $serviceType,
        int                   $scope,
        ?string               $areaId,
        string                $from,
        ?string               $to = null,
        ?string               $excludeId = null
    ): bool {
        $query = $this->model->where('target_type', $targetType->value)
            ->where('service_type', $serviceType->value)
            ->where('scope', $scope)
            ->where('is_active', true);

        if ($areaId) {
            $query->where('area_id', $areaId);
        } else {
            $query->whereNull('area_id');
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Kiểm tra overlap thời gian
        return $query->where(function ($q) use ($from, $to) {
            $q->where(function ($inner) use ($from, $to) {
                // Case 1: Rule mới bắt đầu khi rule cũ đang chạy
                $inner->where('effective_from', '<=', $from)
                    ->where(function ($sub) use ($from) {
                        $sub->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $from);
                    });
            });

            if ($to) {
                $q->orWhere(function ($inner) use ($to) {
                    // Case 2: Rule mới kết thúc khi rule cũ đang chạy
                    $inner->where('effective_from', '<=', $to)
                        ->where(function ($sub) use ($to) {
                            $sub->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $to);
                        });
                })->orWhere(function ($inner) use ($from, $to) {
                    // Case 3: Rule cũ nằm hoàn toàn trong rule mới
                    $inner->where('effective_from', '>=', $from)
                        ->where('effective_from', '<=', $to);
                });
            }
        })->exists();
    }
}
