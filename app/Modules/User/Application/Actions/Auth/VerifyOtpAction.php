<?php

declare(strict_types=1);

namespace Modules\User\Application\Actions\Auth;

use Illuminate\Support\Facades\Hash;
use Modules\User\Application\DTOs\Auth\VerifyOtpDTO;
use Modules\User\Domain\Exceptions\OtpExpiredException;
use Modules\User\Domain\Exceptions\OtpInvalidException;
use Modules\User\Domain\Exceptions\OtpTooManyAttemptsException;
use Modules\User\Domain\Interfaces\UserRepositoryInterface;
use Modules\User\Domain\ValueObjects\Phone;

final class VerifyOtpAction
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Xác minh mã OTP nhập vào.
     * @throws OtpExpiredException
     * @throws OtpInvalidException
     * @throws OtpTooManyAttemptsException
     */
    public function execute(VerifyOtpDTO $dto): true
    {
        $phone = new Phone($dto->phone);

        $otpRecord = $this->userRepo->findLatestOtp((string) $phone, $dto->type);

        // ── Chưa có OTP nào ────────────────────────────────────
        if (! $otpRecord) {
            throw new OtpInvalidException();
        }

        // ── Hết hạn ────────────────────────────────────────────
        if ($otpRecord->isExpired()) {
            throw new OtpExpiredException();
        }

        // ── Đã dùng rồi ────────────────────────────────────────
        if ($otpRecord->isVerified()) {
            throw new OtpInvalidException();
        }

        // ── Quá số lần thử ─────────────────────────────────────
        if ($otpRecord->hasExceededAttempts(self::MAX_ATTEMPTS)) {
            throw new OtpTooManyAttemptsException();
        }

        // ── Sai mã ─────────────────────────────────────────────
        if (! Hash::check($dto->otp, $otpRecord->otp_hash)) {
            $this->userRepo->incrementOtpAttempts($otpRecord);
            throw new OtpInvalidException();
        }

        // ── Đúng mã → đánh dấu đã dùng ────────────────────────
        $this->userRepo->markOtpVerified($otpRecord);

        return true;
    }
}
