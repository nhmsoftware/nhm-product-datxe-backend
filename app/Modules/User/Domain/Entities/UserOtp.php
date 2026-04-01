<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Domain\Enums\UserOtpType;

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
        'last_sent_at',
        'send_count',
        'ip_address',
    ];

    protected $casts = [
        'type'        => UserOtpType::class,
        'expired_at'  => 'datetime',
        'verified_at' => 'datetime',
        'last_sent_at'=> 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expired_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function hasExceededAttempts(int $max = 5): bool
    {
        return $this->attempts >= $max;
    }
}
