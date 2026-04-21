<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\Ride\Model\Enums\RideCallStatus;
use App\Modules\Ride\Model\Enums\RideChatSenderType;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideCallLog extends Model
{
    use HasBigIntId;

    protected $table = 'ride_call_logs';

    protected $fillable = [
        'ride_id',
        'caller_id',
        'callee_id',
        'caller_type',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'caller_type' => RideChatSenderType::class,
        'status' => RideCallStatus::class,
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class, 'ride_id');
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function callee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'callee_id');
    }
}
