<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model;

use Illuminate\Database\Eloquent\Model;

final class RideReject extends Model
{
    protected $table = 'ride_rejects';

    protected $fillable = [
        'ride_id',
        'driver_id',
    ];

    protected $casts = [
        'id'        => 'string',
        'ride_id'   => 'string',
        'driver_id' => 'string',
    ];
}
