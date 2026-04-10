<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model;

use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $driver_id
 * @property string $pickup_address
 * @property numeric $pickup_lat
 * @property numeric $pickup_lng
 * @property string $destination_address
 * @property numeric $destination_lat
 * @property numeric $destination_lng
 * @property int $distance Distance in meters
 * @property int $duration Duration in seconds
 * @property VehicleType $vehicle_type
 * @property RideStatus $status 1: Draft, 2: Pending, 3: Accepted, 4: In Progress, 5: Completed, 6: Cancelled
 * @property numeric $base_price
 * @property numeric $distance_price
 * @property numeric $total_price
 * @property int|null $voucher_id
 * @property bool $is_paid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $customer
 * @property-read User|null $driver
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDestinationAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDestinationLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDestinationLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDistancePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereIsPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride wherePickupAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride wherePickupLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride wherePickupLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereVehicleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereVoucherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride withoutTrashed()
 * @mixin \Eloquent
 */
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
