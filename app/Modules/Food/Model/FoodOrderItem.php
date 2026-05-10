<?php

declare(strict_types=1);

namespace App\Modules\Food\Model;

use App\Modules\Merchant\Model\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FoodOrderItem extends Model
{
    protected $fillable = [
        'food_order_id',
        'menu_item_id',
        'name',
        'quantity',
        'price',
        'notes',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(FoodOrderItemOption::class, 'food_order_item_id');
    }
}
