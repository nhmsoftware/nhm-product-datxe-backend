<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{

    use HasBigIntId;
    protected $table = 'user_devices';

    protected $fillable = [
        'user_id',
        'token',
        'device_id',
        'device_type',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
