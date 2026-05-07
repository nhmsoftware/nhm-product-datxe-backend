<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\User\Model\MerchantProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuCategory extends Model
{
    use HasBigIntId, SoftDeletes;

    protected $table = 'merchant_menu_categories';

    protected $fillable = [
        'merchant_profile_id',
        'name',
        'order',
        'is_active',
    ];

    protected $casts = [
        'id'                  => 'string',
        'merchant_profile_id' => 'string',
        'is_active'           => 'boolean',
        'order'               => 'integer',
    ];

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_profile_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'category_id')->orderBy('order');
    }
}
