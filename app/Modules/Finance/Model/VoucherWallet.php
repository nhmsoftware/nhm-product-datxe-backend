<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model quản lý ví voucher của khách hàng.
 */
final class VoucherWallet extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $table = 'voucher_wallets';

    protected $fillable = [
        'customer_id',
        'voucher_id',
        'saved_at',
        'used_at',
    ];

    protected $casts = [
        'saved_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
