<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $customer_id
 * @property int $balance
 * @property int $total_earned
 * @property int $total_used
 */
class RewardWallet extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $fillable = [
        'customer_id',
        'balance',
        'total_earned',
        'total_used',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'balance' => 'integer',
        'total_earned' => 'integer',
        'total_used' => 'integer',
    ];
}
