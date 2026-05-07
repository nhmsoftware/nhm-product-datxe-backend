<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemSize extends Model
{
    use HasBigIntId;

    protected $table = 'merchant_menu_item_sizes';

    protected $fillable = [
        'menu_item_id',
        'name',
        'price',
        'is_default',
    ];

    protected $casts = [
        'id'           => 'string',
        'menu_item_id' => 'string',
        'price'        => 'decimal:2',
        'is_default'   => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }
}
