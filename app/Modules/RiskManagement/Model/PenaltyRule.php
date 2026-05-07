<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model;

use App\Modules\RiskManagement\Model\Enums\ApplicableRole;
use App\Modules\RiskManagement\Model\Enums\PenaltyType;
use App\Modules\RiskManagement\Model\Enums\ViolationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property ViolationType $violation_type
 * @property ApplicableRole $applicable_role
 * @property int $violation_threshold
 * @property PenaltyType $penalty_type
 * @property int|null $penalty_duration
 * @property float|null $monetary_amount
 * @property int|null $reputation_points
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class PenaltyRule extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'penalty_rules';

    protected $fillable = [
        'name',
        'violation_type',
        'applicable_role',
        'violation_threshold',
        'penalty_type',
        'penalty_duration',
        'monetary_amount',
        'reputation_points',
        'description',
        'is_active',
    ];

    protected $casts = [
        'violation_type'      => ViolationType::class,
        'applicable_role'     => ApplicableRole::class,
        'penalty_type'        => PenaltyType::class,
        'is_active'           => 'boolean',
        'monetary_amount'     => 'float',
        'violation_threshold' => 'integer',
        'penalty_duration'    => 'integer',
        'reputation_points'   => 'integer',
    ];
}
