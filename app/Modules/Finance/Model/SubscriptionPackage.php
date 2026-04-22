<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SubscriptionPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'service_fee_reduction_percent',
        'is_active',
    ];

    protected $casts = [
        'price'                         => 'float',
        'duration_days'                 => 'integer',
        'service_fee_reduction_percent' => 'float',
        'is_active'                     => 'boolean',
    ];
}
