<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantProfile extends Model
{
    use HasBigIntId, SoftDeletes;

    protected $table = 'merchant_profiles';

    protected $fillable = [
        'user_id',
        'store_name',
        'store_address',
        'latitude',
        'longitude',
        'opening_time',
        'closing_time',
        'is_open',
        'business_license',
        'business_license_image',
        'average_rating',
        'total_orders',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_open' => 'boolean',
        'average_rating' => 'decimal:2',
        'total_orders' => 'integer',
    ];

    /**
     * Get the user that owns the merchant profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
