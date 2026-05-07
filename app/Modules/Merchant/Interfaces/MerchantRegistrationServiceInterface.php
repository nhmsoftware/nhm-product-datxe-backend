<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\RegisterMerchantDTO;

interface MerchantRegistrationServiceInterface
{
    /**
     * Submit merchant registration application.
     * UC-52 Register Merchant
     * UC-52 Register Merchant
     */
    public function submitRegistration(RegisterMerchantDTO $dto): ServiceReturn;

    /**
     * Get pending merchant applications.
     * Admin Side
     */
    public function getApplications(): ServiceReturn;

    /**
     * Get application details.
     * Admin Side
     */
    public function getApplicationDetails(string $id): ServiceReturn;

    /**
     * Approve merchant registration.
     * Admin Side
     */
    public function approveRegistration(string $applicationId): ServiceReturn;

    /**
     * Reject merchant registration.
     * Admin Side
     */
    public function rejectRegistration(string $applicationId, string $reason): ServiceReturn;
}
