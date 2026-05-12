<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Modules\Finance\Model\Enums\RefundStatus;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class RefundRequest extends Model
{
    use SoftDeletes;

    protected $table = 'refund_requests';

    protected $fillable = [
        'customer_id',
        'refundable_id',
        'refundable_type',
        'amount',
        'reason',
        'status',
        'admin_note',
        'processed_by',
        'processed_at',
        'refunded_at',
        'evidence',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'refundable_id' => 'string',
        'processed_by' => 'string',
        'amount' => 'decimal:2',
        'status' => RefundStatus::class,
        'evidence' => 'array',
        'processed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function refundable(): MorphTo
    {
        return $this->morphTo();
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
