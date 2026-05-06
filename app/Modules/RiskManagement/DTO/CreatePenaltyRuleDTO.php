<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\DTO;

use Illuminate\Http\Request;

final class CreatePenaltyRuleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $violationType,
        public readonly int $applicableRole,
        public readonly int $violationThreshold,
        public readonly int $penaltyType,
        public readonly ?int $penaltyDuration,
        public readonly ?float $monetaryAmount,
        public readonly ?int $reputationPoints,
        public readonly ?string $description,
        public readonly bool $isActive = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:               $request->string('name')->toString(),
            violationType:      (int) $request->input('violation_type'),
            applicableRole:     (int) $request->input('applicable_role'),
            violationThreshold: (int) $request->input('violation_threshold'),
            penaltyType:        (int) $request->input('penalty_type'),
            penaltyDuration:    $request->input('penalty_duration') ? (int) $request->input('penalty_duration') : null,
            monetaryAmount:     $request->input('monetary_amount') ? (float) $request->input('monetary_amount') : null,
            reputationPoints:   $request->input('reputation_points') ? (int) $request->input('reputation_points') : null,
            description:        $request->input('description'),
            isActive:           $request->boolean('is_active', true),
        );
    }

    public function toArray(): array
    {
        return [
            'name'                => $this->name,
            'violation_type'      => $this->violationType,
            'applicable_role'     => $this->applicableRole,
            'violation_threshold' => $this->violationThreshold,
            'penalty_type'        => $this->penaltyType,
            'penalty_duration'    => $this->penaltyDuration,
            'monetary_amount'     => $this->monetaryAmount,
            'reputation_points'   => $this->reputationPoints,
            'description'         => $this->description,
            'is_active'           => $this->isActive,
        ];
    }
}
