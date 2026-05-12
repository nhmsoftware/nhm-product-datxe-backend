<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model;

use App\Modules\Complaint\Model\Complaint;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class UserViolation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'reason',
        'complaint_id',
        'created_by',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'complaint_id' => 'string',
        'created_by' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
