<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'is_online' => 'boolean',
        'current_lat' => 'decimal:8',
        'current_lng' => 'decimal:8',
        'average_rating' => 'decimal:2',
        'total_trips' => 'integer',
        'cooldown_until' => 'datetime',
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
