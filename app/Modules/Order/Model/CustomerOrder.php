<?php

declare(strict_types=1);

namespace App\Modules\Order\Model;

use Illuminate\Database\Eloquent\Model;

final class CustomerOrder extends Model
{
    protected $table = 'customer_orders_view';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'id'                  => 'string',
        'customer_id'         => 'string',
        'service_type'        => 'string',
        'total_price'         => 'decimal:2',
        'status'              => 'integer',
        'pickup_address'      => 'string',
        'destination_address' => 'string',
        'created_at'          => 'datetime',
    ];
}
