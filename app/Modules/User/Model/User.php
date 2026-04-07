<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
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
        'full_name',
        'gender',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'role'              => UserRole::class,
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
}
