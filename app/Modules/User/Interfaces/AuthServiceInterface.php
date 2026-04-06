<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;

interface AuthServiceInterface
{
    /**
     * POST /authenticate-otp
     * @param string $phone
     * @param UserOtpType $type
     * @return ServiceReturn
     */

    public function sendOtp(string $phone, UserOtpType $type): ServiceReturn;
    /**
     * POST /register
     * @param array $data
     * @return ServiceReturn
     */
    public function register(array $data): ServiceReturn;

    /**
     * POST /login
     * @param array $data
     * @return ServiceReturn
     */
    public function login(array $data): ServiceReturn;

    /**
     * POST /logout
     * @param User $user
     * @param bool $logoutAll
     * @return ServiceReturn
     */
    public function logout(User $user, bool $logoutAll = false): ServiceReturn;

    /**
     * POST /google-login
     * @param array $data
     * @return ServiceReturn
     */
    public function googleLogin(array $data): ServiceReturn;

    /**
     * POST /apple-login
     * @param array $data
     * @return ServiceReturn
     */
    public function appleLogin(array $data): ServiceReturn;

    /**
     * POST /reset-password
     * @param array $data
     * @return ServiceReturn
     */
    public function resetPassword(array $data): ServiceReturn;
}
