<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{

    protected $table = 'user_otp';
    protected $fillable = [
        'phone',
        'otp_hash',
        'type',
        'attempts',
        'expired_at',
        'verified_at',
        'used_at',
        'last_sent_at',
        'send_count',
        'ip_address',
    ];

    protected $casts = [
        'expired_at'  => 'datetime',
        'verified_at' => 'datetime',
        'used_at'     => 'datetime',
        'last_sent_at'=> 'datetime',
    ];

    // Attribute ảo — lưu plain OTP tạm để gửi SMS, không persist DB
    public ?string $plain_code = null;

    public function isExpired(): bool
    {
        return $this->expired_at->isPast();
    }

    /**
     * Verify OTP nhập vào với hash trong DB.
     * Dùng Hash::check thay vì so sánh string trực tiếp.
     */
    public function checkCode(string $plainCode): bool
    {
        return \Illuminate\Support\Facades\Hash::check($plainCode, $this->otp_hash);
    }
}
