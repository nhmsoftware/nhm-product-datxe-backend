<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Model;

use App\Modules\Complaint\Model\Enums\ComplaintStatus;
use App\Modules\Complaint\Model\Enums\ComplaintResolutionAction;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Complaint extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sender_id',
        'complaintable_id',
        'complaintable_type',
        'type',
        'content',
        'evidence',
        'status',
        'resolution_action',
        'admin_note',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'id' => 'string',
        'sender_id' => 'string',
        'complaintable_id' => 'string',
        'processed_by' => 'string',
        'evidence' => 'array',
        'status' => ComplaintStatus::class,
        'resolution_action' => ComplaintResolutionAction::class,
        'processed_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function complaintable(): MorphTo
    {
        return $this->morphTo();
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
