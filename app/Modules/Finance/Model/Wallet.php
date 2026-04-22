<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Wallet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_withdrawn',
    ];

    protected $casts = [
        'user_id'         => 'string', // Always cast BigInt ID to string for Frontend
        'balance'         => 'float',
        'total_earned'    => 'float',
        'total_withdrawn' => 'float',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
