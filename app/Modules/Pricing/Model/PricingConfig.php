<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PricingConfig extends Model
{
    use HasUlids;

    protected $table = 'pricing_configs';

    protected $fillable = [
        'vehicle_type',
        'base_price',
        'distance_rate',
        'time_rate',
        'min_fare',
        'surge_multiplier',
        'commission_rate',
    ];

    protected $casts = [
        'id'               => 'string',
        'vehicle_type'     => 'integer',
        'base_price'       => 'float',
        'distance_rate'    => 'float',
        'time_rate'        => 'float',
        'min_fare'         => 'float',
        'surge_multiplier' => 'float',
        'commission_rate'  => 'float',
    ];
}
