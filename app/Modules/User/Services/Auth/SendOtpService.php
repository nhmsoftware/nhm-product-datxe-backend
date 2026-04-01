<?php

declare(strict_types=1);

namespace App\Modules\User\Services\Auth;

use App\Modules\User\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;

final class SendOtpService
{
    private const OTP_EXPIRY_MINUTES  = 5;
    private const MAX_SEND_PER_DAY    = 10;
    private const RESEND_COOLDOWN_SEC = 60;

    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Tạo và gửi OTP mới (hoặc tái sử dụng nếu còn hạn).
     * Returns plain OTP string (để gửi SMS — không log production).
     */
    public function handle(SendOtpData $data): string
    {
        $existing = $this->userRepo->findLatestOtp($data->phone, $data->type);

        // ── Chặn spam gửi lại ──────────────────────────────────
        if ($existing && $existing->last_sent_at) {
            $secondsSinceLast = now()->diffInSeconds($existing->last_sent_at);
            if ($secondsSinceLast < self::RESEND_COOLDOWN_SEC) {
                $wait = self::RESEND_COOLDOWN_SEC - $secondsSinceLast;
                abort(429, "Vui lòng chờ {$wait} giây trước khi gửi lại.");
            }
        }

        if ($existing && $existing->send_count >= self::MAX_SEND_PER_DAY) {
            abort(429, 'Bạn đã yêu cầu OTP quá nhiều lần trong ngày.');
        }

        // ── Tạo OTP ────────────────────────────────────────────
        $otp = $this->generateOtp();

        $this->userRepo->upsertOtp([
            'phone'        => $data->phone,
            'otp_hash'     => bcrypt($otp),
            'type'         => $data->type->value,
            'attempts'     => 0,
            'expired_at'   => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'last_sent_at' => now(),
            'send_count'   => ($existing->send_count ?? 0) + 1,
            'ip_address'   => $data->ipAddress,
            'verified_at'  => null,
        ]);

        // ── Gửi SMS (event-driven hoặc gọi trực tiếp) ─────────
        // TODO: event(new OtpRequested($data->phone, $otp));
        Log::info("[OTP] phone={$data->phone} otp={$otp} type={$data->type->name}");

        return $otp;
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
