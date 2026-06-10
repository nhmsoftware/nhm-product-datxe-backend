<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model;

use App\Modules\Ride\Model\VehicleTypeRef;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PricingConfig extends Model
{
    use HasUlids;

    protected $table = 'pricing_configs';

    protected $fillable = [
        'vehicle_type',
        'vehicle_type_id',
        'base_price',
        'distance_rate',
        'time_rate',
        'min_fare',
        'surge_multiplier',
        'commission_rate',
        'is_active',
    ];

    protected $casts = [
        'id'               => 'string',
        'vehicle_type'     => 'integer',
        'vehicle_type_id'  => 'integer',
        'base_price'       => 'float',
        'distance_rate'    => 'float',
        'time_rate'        => 'float',
        'min_fare'         => 'float',
        'surge_multiplier' => 'float',
        'commission_rate'  => 'float',
        'is_active'        => 'boolean',
    ];

    public function vehicleTypeRef(): BelongsTo
    {
        return $this->belongsTo(VehicleTypeRef::class, 'vehicle_type_id', 'id');
    }
}
