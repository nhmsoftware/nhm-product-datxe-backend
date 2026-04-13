<?php

declare(strict_types=1);

namespace App\Modules\Auth\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\AppleLoginDTO;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\GoogleLoginDTO;
use App\Modules\Auth\DTO\LoginDTO;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\SendOtpDTO;
use App\Modules\User\Model\User;

interface AuthServiceInterface
{
    /**
     * POST /authenticate-otp
     * Gửi mã OTP dựa trên context (đăng ký / đăng nhập / quên mật khẩu).
     */
    public function sendOtp(SendOtpDTO $dto): ServiceReturn;

    /**
     * POST /register
     * Xác minh OTP → tạo user + profile + device → trả token.
     */
    public function register(RegisterDTO $dto): ServiceReturn;

    /**
     * POST /login
     * Kiểm tra SĐT + Mật khẩu → kiểm tra trạng thái → trả token.
     */
    public function login(LoginDTO $dto): ServiceReturn;

    /**
     * POST /logout
     */
    public function logout(User $user, bool $logoutAll = false): ServiceReturn;

    /**
     * POST /google-login
     * Đăng nhập / tạo tài khoản bằng Google OAuth.
     */
    public function googleLogin(GoogleLoginDTO $dto): ServiceReturn;

    /**
     * POST /apple-login
     * Đăng nhập / tạo tài khoản bằng Apple Sign In.
     */
    public function appleLogin(AppleLoginDTO $dto): ServiceReturn;

    /**
     * POST /forgot-password
     * Xác thực OTP → đặt lại mật khẩu → trả token.
     */
    public function forgotPassword(ForgotPasswordDTO $dto): ServiceReturn;
}
