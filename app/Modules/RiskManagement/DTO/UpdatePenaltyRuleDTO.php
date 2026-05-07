<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\DTO;

use Illuminate\Http\Request;

final class UpdatePenaltyRuleDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?int $violationType,
        public readonly ?int $applicableRole,
        public readonly ?int $violationThreshold,
        public readonly ?int $penaltyType,
        public readonly ?int $penaltyDuration,
        public readonly ?float $monetaryAmount,
        public readonly ?int $reputationPoints,
        public readonly ?string $description,
        public readonly ?bool $isActive,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:               $request->has('name') ? $request->string('name')->toString() : null,
            violationType:      $request->has('violation_type') ? (int) $request->input('violation_type') : null,
            applicableRole:     $request->has('applicable_role') ? (int) $request->input('applicable_role') : null,
            violationThreshold: $request->has('violation_threshold') ? (int) $request->input('violation_threshold') : null,
            penaltyType:        $request->has('penalty_type') ? (int) $request->input('penalty_type') : null,
            penaltyDuration:    $request->has('penalty_duration') ? (int) $request->input('penalty_duration') : null,
            monetaryAmount:     $request->has('monetary_amount') ? (float) $request->input('monetary_amount') : null,
            reputationPoints:   $request->has('reputation_points') ? (int) $request->input('reputation_points') : null,
            description:        $request->input('description'),
            isActive:           $request->has('is_active') ? $request->boolean('is_active') : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
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
        ], fn($value) => !is_null($value));
    }
}
