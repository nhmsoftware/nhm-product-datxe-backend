<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model;

use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ride extends Model
{
    use SoftDeletes;

    protected $table = 'rides';

    protected $fillable = [
        'customer_id',
        'driver_id',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'distance',
        'duration',
        'vehicle_type',
        'status',
        'base_price',
        'distance_price',
        'total_price',
        'voucher_id',
        'is_paid',
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:7',
        'pickup_lng' => 'decimal:7',
        'destination_lat' => 'decimal:7',
        'destination_lng' => 'decimal:7',
        'distance' => 'integer',
        'duration' => 'integer',
        'vehicle_type' => VehicleType::class,
        'status' => RideStatus::class,
        'base_price' => 'decimal:2',
        'distance_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    /**
     * Get the customer who booked the ride.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the driver assigned to the ride.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
