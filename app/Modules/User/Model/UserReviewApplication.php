<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\KycType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property array $snapshot_data
 * @property KycType $kyc_type
 * @property KycStatus $kyc_status
 * @property string|null $cancel_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
        'id'            => 'string',
        'user_id'       => 'string',
        'snapshot_data' => 'array',
        'kyc_type'      => KycType::class,
        'kyc_status'    => KycStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
