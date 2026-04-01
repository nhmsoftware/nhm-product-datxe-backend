<?php

namespace App\Modules\User\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\User\Model\Enums\UserOtpType;

interface AuthServiceInterface
{
    public function sendOtp(string $phone, UserOtpType $type): ServiceReturn;
}
