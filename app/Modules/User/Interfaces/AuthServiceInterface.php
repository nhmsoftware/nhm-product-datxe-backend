<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;

interface AuthServiceInterface
{
    public function sendOtp(string $phone, UserOtpType $type): ServiceReturn;
    public function register(array $data): ServiceReturn;
    public function login(array $data): ServiceReturn;
    public function logout(User $user, bool $logoutAll = false): ServiceReturn;
}
