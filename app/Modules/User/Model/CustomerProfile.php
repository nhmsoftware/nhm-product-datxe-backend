<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Modules\User\Model\Enums\Gender;
use Illuminate\Database\Eloquent\Model;

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
}
