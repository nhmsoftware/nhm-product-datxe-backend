<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Domain\Enums\Gender;

class CustomerProfile extends Model
{
    protected $table = 'customer_profiles';

    protected $fillable = [
        'user_id',
        'full_name',
        'gender',
    ];

    protected $casts = [
        'gender' => Gender::class,
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function savedAddresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerSavedAddress::class, 'customer_id');
    }
}
