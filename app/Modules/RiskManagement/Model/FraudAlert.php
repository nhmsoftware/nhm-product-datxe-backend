<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model;

use App\Modules\RiskManagement\Model\Enums\FraudAlertStatus;
use App\Modules\RiskManagement\Model\Enums\FraudTargetType;
use App\Modules\RiskManagement\Model\Enums\FraudType;
use App\Modules\RiskManagement\Model\Enums\RiskLevel;
use Illuminate\Database\Eloquent\Model;

/**
 * Model đại diện cho một cảnh báo gian lận trong hệ thống.
 */
final class FraudAlert extends Model
{
    protected $table = 'fraud_alerts';

    protected $fillable = [
        'target_type',
        'target_id',
        'fraud_type',
        'risk_level',
        'status',
        'title',
        'description',
        'evidence_metadata',
        'detected_at',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'id'                => 'string',
        'target_type'       => FraudTargetType::class,
        'fraud_type'        => FraudType::class,
        'risk_level'        => RiskLevel::class,
        'status'            => FraudAlertStatus::class,
        'evidence_metadata' => 'array',
        'detected_at'       => 'datetime',
        'handled_at'        => 'datetime',
    ];
}
