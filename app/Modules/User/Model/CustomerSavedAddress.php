<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerSavedAddress extends Model
{
    use HasBigIntId, SoftDeletes;

    protected $table = 'customer_saved_addresses';

    protected $fillable = [
        'customer_id',
        'label',
        'name',
        'address_text',
        'lat',
        'lng',
        'is_default',
        'receiver_name',
        'receiver_phone',
        'note',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'is_default' => 'boolean',
    ];

    // Label constants
    public const LABEL_HOME = 1;
    public const LABEL_COMPANY = 2;
    public const LABEL_FAVORITE_RESTAURANT = 3;
    public const LABEL_OTHER = 4;

    /**
     * Get the customer profile that owns the address.
     */
    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    /**
     * Get the user through customer profile.
     */
    public function user(): BelongsTo
    {
        return $this->customerProfile->user();
    }

    /**
     * Get label text.
     */
    public function getLabelTextAttribute(): string
    {
        return match ($this->label) {
            self::LABEL_HOME => 'Nhà',
            self::LABEL_COMPANY => 'Công ty',
            self::LABEL_FAVORITE_RESTAURANT => 'Nhà hàng yêu thích',
            self::LABEL_OTHER => 'Khác',
            default => 'Khác',
        };
    }

    /**
     * Scope to get addresses for a specific customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to get default address.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}