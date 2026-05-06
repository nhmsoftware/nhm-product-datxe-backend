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
        'airport_base_price',
        'is_active',
    ];

    protected $casts = [
        'id'                  => 'string',
        'base_price'          => 'float',
        'scheduled_surcharge' => 'float',
        'intercity_base_price' => 'float',
        'airport_base_price'   => 'float',
        'is_active'           => 'boolean',
    ];
}
