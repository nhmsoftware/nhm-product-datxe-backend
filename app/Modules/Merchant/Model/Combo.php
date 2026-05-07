<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\User\Model\MerchantProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Combo extends Model
{
    use HasBigIntId, SoftDeletes;

    protected $table = 'merchant_combos';

    protected $fillable = [
        'merchant_profile_id',
        'name',
        'description',
        'price',
        'image_path',
        'is_available',
        'order',
    ];

    protected $casts = [
        'id'                  => 'string',
        'merchant_profile_id' => 'string',
        'price'               => 'decimal:2',
        'is_available'        => 'boolean',
        'order'               => 'integer',
    ];

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_profile_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComboItem::class, 'combo_id');
    }
}
