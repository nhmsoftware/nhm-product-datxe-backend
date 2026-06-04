<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ScheduledPricingRange extends Model
{
    use HasUlids;

    protected $table = 'scheduled_pricing_ranges';

    protected $fillable = [
        'scheduled_pricing_rule_id',
        'start_km',
        'end_km',
        'price',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'id'                        => 'string',
        'scheduled_pricing_rule_id' => 'string',
        'start_km'                  => 'float',
        'end_km'                    => 'float',
        'price'                     => 'float',
        'unit'                      => 'string',
        'is_active'                 => 'boolean',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ScheduledPricingRule::class, 'scheduled_pricing_rule_id');
    }
}
