<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\User\Model\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property int|null $driver_group_id
 * @property int $driver_group_type
 * @property int $vehicle_type
 * @property string $vehicle_name
 * @property int $vehicle_color
 * @property string $vehicle_number
 * @property bool $is_online
 * @property numeric|null $current_lat
 * @property numeric|null $current_lng
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $cooldown_until
 * @property int $cancel_count_today
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $license_number
 * @property string|null $license_front_image
 * @property string|null $license_back_image
 * @property numeric $average_rating
 * @property int $total_trips
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $bank_account_holder
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Modules\User\Model\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereAverageRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereBankAccountHolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereBankAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereCancelCountToday($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereCooldownUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereCurrentLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereCurrentLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereDriverGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereDriverGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereIsOnline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereLicenseBackImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereLicenseFrontImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereTotalTrips($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereVehicleColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereVehicleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereVehicleNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile whereVehicleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverProfile withoutTrashed()
 * @mixin \Eloquent
 */
class DriverProfile extends Model
{
    use HasBigIntId, SoftDeletes;

    protected $table = 'driver_profiles';

    protected $fillable = [
        'user_id',
        'full_name',
        'driver_group_id',
        'driver_group_type',
        'vehicle_type',
        'vehicle_name',
        'vehicle_color',
        'vehicle_number',
        'is_online',
        'current_lat',
        'current_lng',
        'status',
        'cooldown_until',
        'cancel_count_today',
        // Trường thêm từ migration profile
        'license_number',
        'license_front_image',
        'license_back_image',
        'average_rating',
        'total_trips',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
    ];

    protected $casts = [
        'id'                => 'string',
        'is_online'      => 'boolean',
        'current_lat'    => 'decimal:8',
        'current_lng'    => 'decimal:8',
        'average_rating' => 'decimal:2',
        'total_trips'    => 'integer',
        'cooldown_until' => 'datetime',
        'status'         => DriverStatus::class,
    ];

    /**
     * Get the user that owns the driver profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the driver group.
     */
    public function driverGroup(): BelongsTo
    {
        return $this->belongsTo(DriverGroup::class, 'driver_group_id');
    }
}
