<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $store_name
 * @property string|null $store_address
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $opening_time
 * @property string|null $closing_time
 * @property bool $is_open
 * @property string|null $business_license
 * @property string|null $business_license_image
 * @property numeric $average_rating
 * @property int $total_orders
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Modules\User\Model\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereAverageRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereBusinessLicense($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereBusinessLicenseImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereClosingTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereIsOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereOpeningTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereStoreAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereStoreName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereTotalOrders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantProfile withoutTrashed()
 * @mixin \Eloquent
 */
class MerchantProfile extends Model
{
    use HasBigIntId, SoftDeletes;

    protected $table = 'merchant_profiles';

    protected $fillable = [
        'user_id',
        'store_name',
        'store_address',
        'lat',
        'lng',
        'opening_time',
        'closing_time',
        'is_open',
        'business_license',
        'business_license_image',
        'average_rating',
        'total_orders',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
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
