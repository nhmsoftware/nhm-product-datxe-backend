<?php

namespace App\Modules\User\Interfaces;

use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\UserOtp;

interface UserOtpRepositoryInterface
{
    public function findLatestOtp(string $phone, UserOtpType $type): ?UserOtp;
}
