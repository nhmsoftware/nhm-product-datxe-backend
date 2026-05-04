<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DriverSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_id',
        'package_id',
        'started_at',
        'expires_at',
        'status',
        'price_paid',
    ];

    protected $casts = [
        'id'         => 'string',
        'driver_id'  => 'string',
        'package_id' => 'string',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'price_paid' => 'float',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }
}
