<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantOpeningHour extends Model
{
    use HasBigIntId;

    protected $table = 'merchant_opening_hours';

    protected $fillable = [
        'merchant_profile_id',
        'day_of_week',
        'opening_time',
        'closing_time',
        'is_closed',
        'is_overnight',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
        'is_overnight' => 'boolean',
        'day_of_week' => 'integer',
    ];

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class);
    }
}
