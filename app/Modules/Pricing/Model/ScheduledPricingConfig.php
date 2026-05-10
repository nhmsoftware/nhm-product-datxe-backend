<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property float $base_price
 * @property float $scheduled_surcharge
 * @property float $intercity_base_price
 * @property float $airport_base_price
 * @property bool $is_active
 */
final class ScheduledPricingConfig extends Model
{
    use HasUlids;

    protected $table = 'scheduled_pricing_configs';

    protected $fillable = [
        'base_price',
        'scheduled_surcharge',
        'intercity_base_price',
        'intercity_distance_rate',
        'intercity_time_rate',
        'intercity_min_fare',
        'airport_base_price',
        'airport_distance_rate',
        'airport_time_rate',
        'airport_min_fare',
        'delivery_base_price',
        'delivery_distance_rate',
        'delivery_time_rate',
        'delivery_min_fare',
        'is_active',
    ];

    protected $casts = [
        'id'                      => 'string',
        'base_price'              => 'float',
        'scheduled_surcharge'     => 'float',
        'intercity_base_price'    => 'float',
        'intercity_distance_rate' => 'float',
        'intercity_time_rate'     => 'float',
        'intercity_min_fare'      => 'float',
        'airport_base_price'      => 'float',
        'airport_distance_rate'   => 'float',
        'airport_time_rate'       => 'float',
        'airport_min_fare'        => 'float',
        'delivery_base_price'     => 'float',
        'delivery_distance_rate'  => 'float',
        'delivery_time_rate'      => 'float',
        'delivery_min_fare'       => 'float',
        'is_active'               => 'boolean',
    ];
}
