<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Modules\User\Model\Enums\UserOtpType;
use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * @property int $id
 * @property string $phone
 * @property string $otp_hash
 * @property UserOtpType $type
 * @property int $attempts
 * @property \Illuminate\Support\Carbon $expired_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $last_sent_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property int $send_count
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereLastSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereOtpHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereSendCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOtp whereVerifiedAt($value)
 * @mixin \Eloquent
 */
class UserOtp extends Model
{
    use HasBigIntId;

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
        'type' => UserOtpType::class,
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
        return Hash::check($plainCode, $this->otp_hash);
    }
}
