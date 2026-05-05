<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PricingGlobalSetting extends Model
{
    use HasUlids;

    protected $table = 'pricing_global_settings';

    protected $fillable = [
        'is_free_mode',
    ];

    protected $casts = [
        'id'           => 'string',
        'is_free_mode' => 'boolean',
    ];
}
