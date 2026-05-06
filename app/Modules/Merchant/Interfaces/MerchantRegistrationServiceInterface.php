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
     */
    public function submitRegistration(RegisterMerchantDTO $dto): ServiceReturn;

    /**
     * Send OTP for merchant registration.
     * UC-52 Register Merchant
     */
    public function sendOtp(string $userId, string $phone): ServiceReturn;

    /**
     * Verify OTP for merchant registration.
     * UC-52 Register Merchant
     */
    public function verifyOtp(string $userId, string $otp): ServiceReturn;
}
