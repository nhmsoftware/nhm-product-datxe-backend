<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Modules\User\Model\Enums\UserRole;

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
