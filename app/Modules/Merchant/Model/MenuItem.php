<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\User\Model\MerchantProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasBigIntId, SoftDeletes;

    protected $table = 'merchant_menu_items';

    protected $fillable = [
        'merchant_profile_id',
        'category_id',
        'name',
        'description',
        'price',
        'image_path',
        'is_available',
        'order',
        'rating',
        'total_reviews',
    ];

    protected $casts = [
        'id'                  => 'string',
        'merchant_profile_id' => 'string',
        'category_id'         => 'string',
        'price'               => 'decimal:2',
        'is_available'        => 'boolean',
        'order'               => 'integer',
        'rating'              => 'decimal:1',
        'total_reviews'       => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_profile_id');
    }

    public function sizes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MenuItemSize::class, 'menu_item_id');
    }

    public function toppings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MenuItemTopping::class, 'menu_item_id');
    }

    public function getImagePathAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return asset('storage/' . $value);
    }
}
