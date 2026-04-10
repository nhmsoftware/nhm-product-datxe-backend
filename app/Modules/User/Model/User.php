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
use App\Modules\User\Model\CustomerProfile;

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
