<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Interfaces;

use App\Core\Services\ServiceReturn;

interface ViolationServiceInterface
{
    /**
     * Create a violation record for a user
     * UC-108 A5, A6
     */
    public function createViolation(string $userId, string $type, string $reason, ?string $complaintId = null, ?string $createdBy = null): ServiceReturn;

    /**
     * Warn a user (Driver or Customer)
     * UC-110
     */
    public function warnUser(\App\Modules\RiskManagement\DTO\WarnUserDTO $dto): ServiceReturn;

    /**
     * Get violation history of a user
     */
    public function getHistory(string $userId): ServiceReturn;
}
