<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\User\Model\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProfile extends Model
{
    use HasBigIntId, SoftDeletes;

    protected $table = 'customer_profiles';

    protected $fillable = [
        'user_id',
        'full_name',
        'gender',
        'citizen_id',
        'address',
        'avatar',
        'birthday',
    ];

    protected $casts = [
        'gender' => Gender::class,
        'birthday' => 'date',
    ];

    /**
     * Get the user that owns the customer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all saved addresses for this customer.
     */
    public function savedAddresses(): HasMany
    {
        return $this->hasMany(CustomerSavedAddress::class, 'customer_id');
    }

    /**
     * Get the default saved address.
     */
    public function defaultAddress(): ?CustomerSavedAddress
    {
        return $this->savedAddresses()->where('is_default', true)->first();
    }
}
