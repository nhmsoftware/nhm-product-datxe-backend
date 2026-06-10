<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Model lưu lịch sử thay đổi cấu hình giá.
 */
final class PricingConfigHistory extends Model
{
    use HasUlids;

    protected $table = 'pricing_config_history';

    protected $fillable = [
        'vehicle_type',
        'vehicle_type_id',
        'old_config',
        'new_config',
        'admin_id',
    ];

    protected $casts = [
        'vehicle_type' => 'integer',
        'vehicle_type_id' => 'integer',
        'old_config' => 'array',
        'new_config' => 'array',
    ];
}
