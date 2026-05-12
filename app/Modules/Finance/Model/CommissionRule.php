<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Modules\Finance\Model\Enums\CommissionScope;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model quy tắc hoa hồng hệ thống.
 * 
 * @property string $id
 * @property string|null $name
 * @property CommissionServiceType $service_type
 * @property CommissionScope $scope
 * @property string|null $area_id
 * @property float $commission_rate
 * @property float|null $min_commission
 * @property float|null $max_commission
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 */
final class CommissionRule extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'commission_rules';

    protected $fillable = [
        'name',
        'service_type',
        'scope',
        'area_id',
        'commission_rate',
        'min_commission',
        'max_commission',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'id'              => 'string',
        'service_type'    => CommissionServiceType::class,
        'scope'           => CommissionScope::class,
        'commission_rate' => 'float',
        'min_commission'  => 'float',
        'max_commission'  => 'float',
        'is_active'       => 'boolean',
        'effective_from'  => 'datetime',
        'effective_to'    => 'datetime',
    ];
}
