<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\Ride\Model\Enums\RideChatMessageStatus;
use App\Modules\Ride\Model\Enums\RideChatSenderType;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideChatMessage extends Model
{
    use HasBigIntId;

    protected $table = 'ride_chat_messages';

    protected $fillable = [
        'ride_id',
        'sender_id',
        'sender_type',
        'message',
        'status',
    ];

    protected $casts = [
        'sender_type' => RideChatSenderType::class,
        'status' => RideChatMessageStatus::class,
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class, 'ride_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
