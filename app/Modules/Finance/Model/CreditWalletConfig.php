<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property float $min_balance
 * @property bool $auto_lock
 * @property string|null $commission_rule
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class CreditWalletConfig extends Model
{
    protected $table = 'credit_wallet_configs';

    protected $fillable = [
        'min_balance',
        'auto_lock',
        'commission_rule',
        'updated_by',
    ];

    protected $casts = [
        'min_balance' => 'float',
        'auto_lock'    => 'boolean',
    ];
}
