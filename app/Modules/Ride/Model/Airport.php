<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property float $lat
 * @property float $lng
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Airport extends Model
{
    protected $table = 'airports';

    protected $fillable = [
        'name',
        'code',
        'lat',
        'lng',
        'is_active',
    ];

    protected $casts = [
        'lat'       => 'decimal:7',
        'lng'       => 'decimal:7',
        'is_active' => 'boolean',
    ];
}
