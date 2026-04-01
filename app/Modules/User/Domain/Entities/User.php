<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Modules\User\Domain\Enums\UserRole;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'phone',
        'email',
        'password',
        'role',
        'is_verified',
        'google_id',
        'apple_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'role'        => UserRole::class,
        'is_verified' => 'boolean',
        'deleted_at'  => 'datetime',
    ];

    // ─── Relations ───────────────────────────────────────────────
    public function customerProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\Modules\User\Domain\Entities\CustomerProfile::class, 'user_id');
    }

    public function driverProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\Modules\User\Domain\Entities\DriverProfile::class, 'user_id');
    }

    public function devices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\User\Domain\Entities\UserDevice::class, 'user_id');
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
