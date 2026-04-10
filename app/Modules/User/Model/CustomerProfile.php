<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\User\Model\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property Gender|null $gender
 * @property \Illuminate\Support\Carbon|null $birthday
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $citizen_id
 * @property string|null $address
 * @property string|null $avatar
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\User\Model\CustomerSavedAddress> $savedAddresses
 * @property-read int|null $saved_addresses_count
 * @property-read \App\Modules\User\Model\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereCitizenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerProfile withoutTrashed()
 * @mixin \Eloquent
 */
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
