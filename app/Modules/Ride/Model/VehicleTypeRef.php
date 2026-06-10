<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model;

use Illuminate\Database\Eloquent\Model;

final class VehicleTypeRef extends Model
{
    protected $table = 'vehicle_types';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'code',
        'name_vi',
        'description_vi',
        'capacity',
        'estimated_wait_time',
        'service_scopes',
        'is_bookable',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'id' => 'integer',
        'capacity' => 'integer',
        'service_scopes' => 'array',
        'is_bookable' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
