<?php

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\User\Interfaces\AuthServiceInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Repositories\UserOtpRepository;
use App\Modules\User\Repositories\UserRepository;

class AuthService extends BaseService implements AuthServiceInterface
{
    public function __construct(
        protected UserRepository    $userRepository,
        protected UserOtpRepository $otpRepository,
    )
    {
    }

    public function sendOtp(string $phone, UserOtpType $type): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($phone, $type) {
                $user = $this->userRepository->findByPhone($phone);
                if (!$user) {
                    $this->throw("User not found.", 429);
                }
                // TODO: ....

                return $this->success(
                    data: [

                    ],
                    message: "Gửi OTP thành công",
                );
            },
        );
    }


}
