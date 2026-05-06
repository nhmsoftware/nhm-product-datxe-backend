<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model;

use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\RiskManagement\Model\Enums\CancellationFeeType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property RideType $ride_type
 * @property int $min_minutes_before_pickup
 * @property CancellationFeeType $fee_type
 * @property float $fee_value
 * @property bool $is_active
 * @property string|null $description
 */
final class ScheduledRideCancellationConfig extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'scheduled_ride_cancellation_configs';

    protected $fillable = [
        'ride_type',
        'min_minutes_before_pickup',
        'fee_type',
        'fee_value',
        'is_active',
        'description',
    ];

    protected $casts = [
        'ride_type'                 => RideType::class,
        'fee_type'                  => CancellationFeeType::class,
        'fee_value'                 => 'float',
        'min_minutes_before_pickup' => 'integer',
        'is_active'                 => 'boolean',
    ];
}
