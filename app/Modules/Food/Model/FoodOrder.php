<?php

declare(strict_types=1);

namespace App\Modules\Food\Model;

use App\Modules\Food\Model\Enums\FoodOrderStatus;
use App\Modules\User\Model\MerchantProfile;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class FoodOrder extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'merchant_id',
        'status',
        'subtotal_price',
        'delivery_fee',
        'service_fee',
        'discount_amount',
        'total_price',
        'delivery_address',
        'delivery_lat',
        'delivery_lng',
        'customer_phone',
        'notes',
        'voucher_code',
        'ride_id',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'merchant_id' => 'string',
        'ride_id' => 'string',
        'status' => FoodOrderStatus::class,
        'subtotal_price' => 'float',
        'delivery_fee' => 'float',
        'service_fee' => 'float',
        'discount_amount' => 'float',
        'total_price' => 'float',
        'delivery_lat' => 'float',
        'delivery_lng' => 'float',
    ];

    protected $appends = [
        'merchant_lat',
        'merchant_lng',
        'driver_lat',
        'driver_lng',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FoodOrderItem::class, 'food_order_id');
    }

    public function ride(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Ride\Model\Ride::class, 'ride_id');
    }

    public function getMerchantLatAttribute(): ?float
    {
        return $this->merchant ? (float)$this->merchant->latitude : null;
    }

    public function getMerchantLngAttribute(): ?float
    {
        return $this->merchant ? (float)$this->merchant->longitude : null;
    }

    public function getDriverLatAttribute(): ?float
    {
        $ride = $this->ride;
        if ($ride && $ride->driver_id) {
            $driverProfile = \App\Modules\User\Model\DriverProfile::where('user_id', $ride->driver_id)->first();
            return $driverProfile ? (float)$driverProfile->current_lat : null;
        }
        return null;
    }

    public function getDriverLngAttribute(): ?float
    {
        $ride = $this->ride;
        if ($ride && $ride->driver_id) {
            $driverProfile = \App\Modules\User\Model\DriverProfile::where('user_id', $ride->driver_id)->first();
            return $driverProfile ? (float)$driverProfile->current_lng : null;
        }
        return null;
    }
}
