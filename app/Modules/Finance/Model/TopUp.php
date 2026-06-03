<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Modules\Finance\Model\Enums\TopUpStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string      $id
 * @property string      $user_id
 * @property string      $wallet_id
 * @property float       $amount
 * @property TopUpStatus $status
 * @property string      $payment_method   Code của phương thức: momo, zalopay, bank_transfer...
 * @property string|null $external_id      Mã giao dịch tham chiếu từ Payment Gateway
 * @property array|null  $metadata         Dữ liệu bổ sung từ Gateway callback
 */
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
        'expired_at',
    ];

    protected $casts = [
        'id'         => 'string',
        'user_id'    => 'string',
        'wallet_id'  => 'string',
        'amount'     => 'float',
        'status'     => TopUpStatus::class,
        'metadata'   => 'array',
        'expired_at' => 'datetime',
    ];
}
