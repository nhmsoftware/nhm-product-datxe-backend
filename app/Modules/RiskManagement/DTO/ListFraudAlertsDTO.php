<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\DTO;

use App\Modules\RiskManagement\Http\Requests\ListFraudAlertsRequest;

/**
 * DTO lọc danh sách cảnh báo gian lận.
 */
final class ListFraudAlertsDTO
{
    public function __construct(
        public readonly ?string $keyword,
        public readonly ?int    $targetType,
        public readonly ?int    $riskLevel,
        public readonly ?int    $status,
        public readonly ?int    $fraudType,
        public readonly int     $perPage,
        public readonly int     $page,
    ) {
    }

    public static function fromRequest(ListFraudAlertsRequest $request): self
    {
        return new self(
            keyword:    $request->string('keyword')->toString() ?: null,
            targetType: $request->has('target_type') ? (int) $request->input('target_type') : null,
            riskLevel:  $request->has('risk_level') ? (int) $request->input('risk_level') : null,
            status:     $request->has('status') ? (int) $request->input('status') : null,
            fraudType:  $request->has('fraud_type') ? (int) $request->input('fraud_type') : null,
            perPage:    (int) $request->input('per_page', 20),
            page:       (int) $request->input('page', 1),
        );
    }

    public function toArray(): array
    {
        return [
            'keyword'     => $this->keyword,
            'target_type' => $this->targetType,
            'risk_level'  => $this->riskLevel,
            'status'      => $this->status,
            'fraud_type'  => $this->fraudType,
        ];
    }
}
