<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\Finance\Model\Enums\RewardTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardTransaction extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $fillable = [
        'customer_id',
        'type',
        'points',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'type' => RewardTransactionType::class,
        'points' => 'integer',
    ];

    /**
     * @return MorphTo
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
