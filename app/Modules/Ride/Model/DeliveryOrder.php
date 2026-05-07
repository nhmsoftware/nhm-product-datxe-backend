<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * UC-25: Model thông tin giao hàng đi kèm một chuyến xe Delivery.
 *
 * @property string      $id
 * @property int         $ride_id
 * @property string      $sender_name
 * @property string      $sender_phone
 * @property string      $receiver_name
 * @property string      $receiver_phone
 * @property string      $goods_type
 * @property float       $goods_weight
 * @property string|null $goods_note
 * @property bool        $is_fragile
 * @property-read Ride   $ride
 */
class DeliveryOrder extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $table = 'delivery_orders';

    protected $fillable = [
        'ride_id',
        'sender_name',
        'sender_phone',
        'receiver_name',
        'receiver_phone',
        'goods_type',
        'goods_weight',
        'goods_note',
        'is_fragile',
    ];

    protected $casts = [
        'id'           => 'string',
        'goods_weight' => 'float',
        'is_fragile'   => 'boolean',
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }
}
