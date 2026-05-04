<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TopUp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount',
        'status',
        'payment_method',
        'external_id',
        'metadata',
    ];

    protected $casts = [
        'id'        => 'string',
        'user_id'   => 'string',
        'wallet_id' => 'string',
        'amount'    => 'float',
        'metadata'  => 'array',
    ];
}
