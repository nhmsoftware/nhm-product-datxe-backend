<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Modules\Finance\Model\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $type           PaymentMethodType value
 * @property string $code           Unique code: momo, zalopay, vnpay, bank_card, bank_transfer
 * @property string $name           Display name
 * @property bool   $is_active
 * @property float  $min_amount
 * @property float  $max_amount
 * @property array|null $transfer_info  Bank transfer info: {bank_name, account_number, account_name, bank_code, qr_url}
 * @property string|null $icon_url
 * @property array|null $metadata
 * @property int    $sort_order
 * @property int|null $updated_by
 */
final class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'code',
        'name',
        'is_active',
        'min_amount',
        'max_amount',
        'transfer_info',
        'icon_url',
        'metadata',
        'sort_order',
        'updated_by',
    ];

    protected $casts = [
        'id'            => 'string',
        'type'          => PaymentMethodType::class,
        'is_active'     => 'boolean',
        'min_amount'    => 'float',
        'max_amount'    => 'float',
        'transfer_info' => 'array',
        'metadata'      => 'array',
        'sort_order'    => 'integer',
    ];
}
