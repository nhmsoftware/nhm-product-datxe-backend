<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\DTO;

use App\Modules\RiskManagement\Model\FraudAlert;

/**
 * DTO đại diện cho một cảnh báo gian lận trả về cho Admin.
 */
final class AdminFraudAlertDTO
{
    public function __construct(
        public readonly string $id,
        public readonly int    $targetType,
        public readonly string $targetTypeLabel,
        public readonly string $targetId,
        public readonly int    $fraudType,
        public readonly string $fraudTypeLabel,
        public readonly int    $riskLevel,
        public readonly string $riskLevelLabel,
        public readonly string $riskLevelColor,
        public readonly int    $status,
        public readonly string $statusLabel,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?array  $evidenceMetadata,
        public readonly string $detectedAt,
        public readonly ?string $handledBy,
        public readonly ?string $handledAt,
    ) {
    }

    public static function fromModel(FraudAlert $alert): self
    {
        return new self(
            id:                (string) $alert->id,
            targetType:        $alert->target_type->value,
            targetTypeLabel:   $alert->target_type->getLabel(),
            targetId:          (string) $alert->target_id,
            fraudType:         $alert->fraud_type->value,
            fraudTypeLabel:    $alert->fraud_type->getLabel(),
            riskLevel:         $alert->risk_level->value,
            riskLevelLabel:    $alert->risk_level->getLabel(),
            riskLevelColor:    $alert->risk_level->getColor(),
            status:            $alert->status->value,
            statusLabel:       $alert->status->getLabel(),
            title:             $alert->title,
            description:       $alert->description,
            evidenceMetadata:  $alert->evidence_metadata,
            detectedAt:        $alert->detected_at->toIso8601String(),
            handledBy:         $alert->handled_by,
            handledAt:         $alert->handled_at?->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'target_type'       => $this->targetType,
            'target_type_label' => $this->targetTypeLabel,
            'target_id'         => $this->targetId,
            'fraud_type'        => $this->fraudType,
            'fraud_type_label'  => $this->fraudTypeLabel,
            'risk_level'        => $this->riskLevel,
            'risk_level_label'  => $this->riskLevelLabel,
            'risk_level_color'  => $this->riskLevelColor,
            'status'            => $this->status,
            'status_label'      => $this->statusLabel,
            'title'             => $this->title,
            'description'       => $this->description,
            'evidence_metadata' => $this->evidenceMetadata,
            'detected_at'       => $this->detectedAt,
            'handled_by'        => $this->handledBy,
            'handled_at'        => $this->handledAt,
        ];
    }
}
