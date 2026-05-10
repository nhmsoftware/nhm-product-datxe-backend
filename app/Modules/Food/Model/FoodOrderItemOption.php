<?php

declare(strict_types=1);

namespace App\Modules\Food\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FoodOrderItemOption extends Model
{
    protected $fillable = [
        'food_order_item_id',
        'option_name',
        'option_value',
        'price',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(FoodOrderItem::class, 'food_order_item_id');
    }
}
