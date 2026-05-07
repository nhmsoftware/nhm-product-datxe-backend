<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model quản lý ví voucher của khách hàng.
 * @property string $id
 * @property string $customer_id
 * @property string $voucher_id
 * @property \Illuminate\Support\Carbon|null $saved_at
 * @property \Illuminate\Support\Carbon|null $used_at
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
        'id' => 'string',
        'customer_id' => 'string',
        'voucher_id' => 'string',
        'saved_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Relationship tới Voucher.
     */
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }
}
