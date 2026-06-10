<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ScheduledPricingRule extends Model
{
    use HasUlids;

    protected $table = 'scheduled_pricing_rules';

    protected $fillable = [
        'service_type',
        'ride_mode',
        'vehicle_type',
        'vehicle_type_id',
        'airport_id',
        'is_active',
    ];

    protected $casts = [
        'id'           => 'string',
        'service_type' => 'integer',
        'ride_mode'    => 'string',
        'vehicle_type' => 'integer',
        'vehicle_type_id' => 'integer',
        'airport_id'   => 'string',
        'is_active'    => 'boolean',
    ];

    public function ranges(): HasMany
    {
        return $this->hasMany(ScheduledPricingRange::class, 'scheduled_pricing_rule_id');
    }
}
