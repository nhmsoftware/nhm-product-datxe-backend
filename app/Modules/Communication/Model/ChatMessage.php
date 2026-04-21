<?php

declare(strict_types=1);

namespace App\Modules\Communication\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $ride_id
 * @property string $sender_id
 * @property string $receiver_id
 * @property string $message
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ChatMessage extends Model
{
    use HasBigIntId;

    protected $table = 'chat_messages';

    protected $fillable = [
        'ride_id',
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
    ];

    protected $casts = [
        'id'          => 'string',
        'ride_id'     => 'string',
        'sender_id'   => 'string',
        'receiver_id' => 'string',
        'is_read'     => 'boolean',
    ];

    /**
     * @return BelongsTo<Ride, ChatMessage>
     */
    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class, 'ride_id');
    }

    /**
     * @return BelongsTo<User, ChatMessage>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, ChatMessage>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
