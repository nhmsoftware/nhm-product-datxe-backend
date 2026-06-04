<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model;

use App\Modules\Pricing\Model\Enums\ScheduledDispatchMode;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PricingGlobalSetting extends Model
{
    use HasUlids;

    protected $table = 'pricing_global_settings';

    protected $fillable = [
        'is_free_mode',
        'scheduled_dispatch_mode',
        'auto_push_internal',
    ];

    protected $casts = [
        'id'                      => 'string',
        'is_free_mode'            => 'boolean',
        'scheduled_dispatch_mode' => ScheduledDispatchMode::class,
        'auto_push_internal'      => 'boolean',
    ];
}
