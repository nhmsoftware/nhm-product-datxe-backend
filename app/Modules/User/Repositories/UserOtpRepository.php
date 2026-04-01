<?php

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\UserOtpRepositoryInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\UserOtp;

class UserOtpRepository extends BaseRepository implements UserOtpRepositoryInterface
{

    public function getModel()
    {
       return UserOtp::class;
    }

    public function findLatestOtp(string $phone, UserOtpType $type): ?UserOtp
    {
        return $this->query()->where('phone', $phone)
            ->where('type', $type->value)
            ->latest('created_at')
            ->first();
    }
}
