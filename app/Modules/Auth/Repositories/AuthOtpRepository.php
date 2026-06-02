<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Auth\Interfaces\AuthOtpRepositoryInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\UserOtp;

class AuthOtpRepository extends BaseRepository implements AuthOtpRepositoryInterface
{
    public function getModel(): string
    {
        return UserOtp::class;
    }

    /**
     * Lấy OTP mới nhất của số điện thoại theo type (bất kể trạng thái).
     * Dùng để kiểm tra throttle và lấy record để verify.
     * @param string $phone
     * @param UserOtpType $type
     * @return UserOtp|null
     */
    public function getLastOtp(string $phone, UserOtpType $type): ?UserOtp
    {
        return $this->getQuery()
            ->where('phone', $phone)
            ->where('type', $type->value)
            ->latest('created_at')
            ->first();
    }

    /**
     * Lấy OTP đã được verify thành công mới nhất.
     * Dùng để kiểm tra trước khi cho phép đăng ký.
     */
    public function getLastVerified(string $phone, UserOtpType $type): ?UserOtp
    {
        return $this->getQuery()
            ->where('phone', $phone)
            ->where('type', $type->value)
            ->whereNotNull('verified_at')
            ->whereNull('used_at')           // chưa consume (chưa dùng để tạo tài khoản)
            ->where('expired_at', '>', now())
            ->latest('created_at')
            ->first();
    }

    /**
     * Đếm số lần đã gửi OTP trong ngày (theo giờ UTC).
     * Dùng để áp giới hạn MAX_SEND_PER_DAY.
     */
    public function countSentToday(string $phone, UserOtpType $type): int
    {
        return $this->getQuery()
            ->where('phone', $phone)
            ->where('type', $type->value)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Tạo bản ghi OTP mới.
     * - Sinh code 6 chữ số
     * - Hash bằng bcrypt để không lộ plain-text trong DB
     * - Lưu plain-text tạm vào attribute ảo `plain_code` để service gửi SMS
     */
    public function generateOtp(string $phone, UserOtpType $type): UserOtp
    {
        // TODO: [PRODUCTION] Uncomment dòng dưới để tạo OTP ngẫu nhiên khi lên production
        // $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // [DEV/TEST ONLY] OTP cố định để test — XÓA hoặc comment dòng này khi lên production
        $plainCode = '123456';

        /** @var UserOtp $otp */
        $otp = $this->getQuery()->create([
            'phone'        => $phone,
            'otp_hash'     => bcrypt($plainCode),
            'type'         => $type,
            'attempts'     => 0,
            'expired_at'   => now()->addMinutes(10), // Tăng lên 10 phút để kịp nộp hồ sơ
            'verified_at'  => null,
            'used_at'      => null,
            'last_sent_at' => now(),
            'send_count'   => 1,
            'ip_address'   => request()->ip(),
        ]);

        // Gắn plain-text vào attribute ảo để AuthService đọc rồi gửi SMS
        // KHÔNG persist vào DB
        $otp->plain_code = $plainCode;

        return $otp;
    }

    /**
     * Tăng số lần nhập sai OTP.
     */
    public function incrementAttempts(UserOtp $otp): void
    {
        $otp->increment('attempts');
    }

    /**
     * Đánh dấu OTP đã xác minh thành công (bước verify OTP).
     * Record này vẫn còn dùng được để tạo tài khoản / đăng nhập.
     */
    public function markAsVerified(UserOtp $otp): void
    {
        $otp->update(['verified_at' => now()]);
    }

    /**
     * Đánh dấu OTP đã được consume — dùng xong sau register/login.
     * Ngăn replay attack: cùng 1 OTP không thể dùng để tạo tài khoản 2 lần.
     */
    public function markLatestAsUsed(string $phone, UserOtpType $type): void
    {
        $this->getQuery()
            ->where('phone', $phone)
            ->where('type', $type->value)
            ->whereNotNull('verified_at')
            ->whereNull('used_at')
            ->latest('created_at')
            ->first()
            ?->update(['used_at' => now()]);
    }
}
