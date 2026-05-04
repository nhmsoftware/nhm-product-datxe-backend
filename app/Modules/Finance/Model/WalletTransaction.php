<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Modules\Finance\Model\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class WalletTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'id'             => 'string',
        'wallet_id'      => 'string',
        'type'           => WalletTransactionType::class,
        'amount'         => 'float',
        'balance_before' => 'float',
        'balance_after'  => 'float',
        'reference_id'   => 'string',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
