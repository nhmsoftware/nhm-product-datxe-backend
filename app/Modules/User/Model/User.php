<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\User\Model\Enums\Gender;
use App\Modules\User\Model\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;



/**
 * @property int $id
 * @property string|null $phone
 * @property string|null $email
 * @property bool $is_verified
 * @property bool $is_phone_verified
 * @property string|null $google_id
 * @property string|null $apple_id
 * @property string $password
 * @property UserRole $role
 * @property bool $is_active
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $avatar
 * @property string|null $address
 * @property string|null $citizen_id
 * @property Gender $gender
 * @property-read \App\Modules\User\Model\CustomerProfile|null $customerProfile
 * @property-read \App\Modules\User\Model\DriverProfile|null $driverProfile
 * @property-read mixed $birthday
 * @property-read string|null $full_name
 * @property-read \App\Modules\User\Model\MerchantProfile|null $merchantProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\User\Model\UserDevice> $userDevices
 * @property-read int|null $user_devices_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAppleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCitizenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGoogleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsPhoneVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes, HasBigIntId;

    protected $table = 'users';

    protected $fillable = [
        'phone',
        'email',
        'password',
        'role',
        'is_verified',
        'is_phone_verified',
        'is_active',
        'google_id',
        'apple_id',
        'avatar',
        'address',
        'citizen_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'role'              => UserRole::class,
        'gender'            => Gender::class,
        'is_verified'       => 'boolean',
        'is_phone_verified' => 'boolean',
        'is_active'         => 'boolean',
        'deleted_at'        => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────
    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function merchantProfile(): HasOne
    {
        return $this->hasOne(MerchantProfile::class);
    }

    public function userDevices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────
    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isDriver(): bool
    {
        return $this->role === UserRole::Driver;
    }

    public function isMerchant(): bool
    {
        return $this->role === UserRole::Merchants;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    // ─── Accessors ────────────────────────────────────────────────
    public function getFullNameAttribute(): ?string
    {
        return $this->customerProfile?->full_name
            ?? $this->driverProfile?->full_name
            ?? $this->merchantProfile?->store_name;
    }

    public function getGenderAttribute()
    {
        return $this->customerProfile?->gender;
    }

    public function getAvatarAttribute(): ?string
    {
        // Ưu tiên avatar ở bảng core users nếu có, nếu không lấy ở profile
        return $this->attributes['avatar']
            ?? $this->customerProfile?->avatar
            ?? $this->driverProfile?->avatar;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->attributes['address']
            ?? $this->customerProfile?->address
            ?? $this->merchantProfile?->store_address;
    }

    public function getBirthdayAttribute()
    {
        return $this->customerProfile?->birthday;
    }

    public function getCitizenIdAttribute(): ?string
    {
        return $this->attributes['citizen_id']
            ?? $this->customerProfile?->citizen_id
            ?? $this->driverProfile?->citizen_id;
    }

    // ─── Account Status Helpers ──────────────────────────────────

    /**
     * Kiểm tra tài khoản có bị khóa hay không.
     * Tài khoản bị khóa khi: is_active = false VÀ deleted_at != null
     */
    public function isLocked(): bool
    {
        return !$this->is_active && $this->deleted_at !== null;
    }

    /**
     * Kiểm tra tài khoản có đang active hay không.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->deleted_at === null;
    }
}
