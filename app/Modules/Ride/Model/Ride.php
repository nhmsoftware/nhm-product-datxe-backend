<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Enums\RideTrackingStatus;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $customer_id
 * @property string|null $driver_id
 * @property string $pickup_address
 * @property numeric $pickup_lat
 * @property numeric $pickup_lng
 * @property string $destination_address
 * @property numeric $destination_lat
 * @property numeric $destination_lng
 * @property int $distance Distance in meters
 * @property int $duration Duration in seconds
 * @property VehicleType $vehicle_type
 * @property RideType $ride_type
 * @property string|null $travel_date
 * @property string|null $travel_time
 * @property string|null $airport_id
 * @property int|null $airport_direction 1: To Airport, 2: From Airport
 * @property RideStatus $status 1: Draft, 2: Pending, 3: Accepted, 4: In Progress, 5: Completed, 6: Cancelled
 * @property numeric $base_price
 * @property numeric $distance_price
 * @property numeric $total_price
 * @property int|null $voucher_id
 * @property string|null $voucher_code
 * @property numeric $discount_amount
 * @property bool $is_paid
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
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
    use SoftDeletes, HasBigIntId;

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
        'ride_type',
        'travel_date',
        'travel_time',
        'airport_id',
        'airport_direction',
        'status',
        'base_price',
        'distance_price',
        'time_fare',
        'total_price',
        'voucher_id',
        'voucher_code',
        'discount_amount',
        'tracking_status',
        'is_paid',
        'cancel_reason',
        'cancellation_fee',
        'service_fee',
        'driver_earnings',
        'driver_assigned_at',
        'driver_arrived_at',
        'pickup_proof_photo_url',
        'pickup_proof_captured_at',
        'pickup_proof_skip_reason',
        'pickup_proof_note',
        'delivery_proof_photo_url',
        'delivery_proof_captured_at',
        'delivery_proof_skip_reason',
        'delivery_proof_note',
        'tracking_last_ping_at',
        'chauffeur_license_plate',
        'chauffeur_vehicle_type',
        'chauffeur_brand',
        'chauffeur_color',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'driver_id' => 'string',
        'pickup_lat' => 'decimal:7',
        'pickup_lng' => 'decimal:7',
        'destination_lat' => 'decimal:7',
        'destination_lng' => 'decimal:7',
        'distance' => 'integer',
        'duration' => 'integer',
        'vehicle_type' => VehicleType::class,
        'ride_type' => RideType::class,
        'travel_date' => 'date',
        'travel_time' => 'string',
        'airport_id' => 'string',
        'airport_direction' => 'integer',
        'status' => RideStatus::class,
        'tracking_status' => RideTrackingStatus::class,
        'base_price' => 'decimal:2',
        'distance_price' => 'decimal:2',
        'time_fare' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'cancellation_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'driver_earnings' => 'decimal:2',
        'driver_assigned_at'          => 'datetime',
        'driver_arrived_at'           => 'datetime',
        'pickup_proof_captured_at'    => 'datetime',
        'delivery_proof_captured_at'  => 'datetime',
        'tracking_last_ping_at'       => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    /**
     * Get all complaints for the ride.
     */
    public function complaints(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Modules\Complaint\Model\Complaint::class, 'complaintable');
    }

    /**
     * Get all rejects for the ride.
     */
    public function rejects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RideReject::class, 'ride_id', 'id');
    }
}

