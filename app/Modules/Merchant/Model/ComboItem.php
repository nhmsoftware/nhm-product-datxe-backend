<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItem extends Model
{
    use HasBigIntId;

    protected $table = 'merchant_combo_items';

    protected $fillable = [
        'combo_id',
        'menu_item_id',
        'quantity',
    ];

    protected $casts = [
        'id'           => 'string',
        'combo_id'     => 'string',
        'menu_item_id' => 'string',
        'quantity'     => 'integer',
    ];

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class, 'combo_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }
}
