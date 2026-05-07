<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemTopping extends Model
{
    use HasBigIntId;

    protected $table = 'merchant_menu_item_toppings';

    protected $fillable = [
        'menu_item_id',
        'name',
        'price',
        'max_quantity',
        'is_required',
        'is_available',
    ];

    protected $casts = [
        'id'           => 'string',
        'menu_item_id' => 'string',
        'price'        => 'decimal:2',
        'max_quantity' => 'integer',
        'is_required'  => 'boolean',
        'is_available' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }
}
