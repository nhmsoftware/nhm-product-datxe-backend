<?php

declare(strict_types=1);

namespace App\Modules\User\Services\Auth;

use App\Modules\User\Exceptions\OtpExpiredException;
use App\Modules\User\Exceptions\OtpInvalidException;
use App\Modules\User\Exceptions\OtpTooManyAttemptsException;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

final class VerifyOtpService
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Xác minh mã OTP nhập vào.
     *
     * @throws OtpExpiredException
     * @throws OtpInvalidException
     * @throws OtpTooManyAttemptsException
     */
    public function handle(VerifyOtpData $data): true
    {
        $otpRecord = $this->userRepo->findLatestOtp($data->phone, $data->type);

        if (! $otpRecord) {
            throw new OtpInvalidException();
        }

        if ($otpRecord->isExpired()) {
            throw new OtpExpiredException();
        }

        if ($otpRecord->isVerified()) {
            throw new OtpInvalidException();
        }

        if ($otpRecord->hasExceededAttempts(self::MAX_ATTEMPTS)) {
            throw new OtpTooManyAttemptsException();
        }

        if (! Hash::check($data->otp, $otpRecord->otp_hash)) {
            $this->userRepo->incrementOtpAttempts($otpRecord);
            throw new OtpInvalidException();
        }

        $this->userRepo->markOtpVerified($otpRecord);

        return true;
    }
}
