<?php

declare(strict_types=1);

namespace App\Modules\Driver\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\Driver\Model\Enums\KycStatus;
use App\Modules\Driver\Model\Enums\KycType;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hồ sơ KYC chờ duyệt — Aggregate Root của Driver Registration (UC-30).
 *
 * @property int             $id
 * @property int             $user_id
 * @property array           $snapshot_data
 * @property KycType         $kyc_type
 * @property KycStatus       $kyc_status
 * @property string|null     $cancel_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User       $user
 */
class UserReviewApplication extends Model
{
    use HasBigIntId;

    protected $table = 'user_review_applications';

    protected $fillable = [
        'user_id',
        'snapshot_data',
        'kyc_type',
        'kyc_status',
        'cancel_reason',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'kyc_type'      => KycType::class,
        'kyc_status'    => KycStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Danh sách file đính kèm hồ sơ.
     */
    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileRecord::class, 'fileable_id')
            ->where('fileable_type', \App\Modules\Driver\Model\Enums\FileableType::DRIVER_REVIEW_APPLICATION->value);
    }
}
