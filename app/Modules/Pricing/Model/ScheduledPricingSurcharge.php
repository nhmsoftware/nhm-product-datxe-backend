<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ScheduledPricingSurcharge extends Model
{
    use HasUlids;

    protected $table = 'scheduled_pricing_surcharges';

    protected $fillable = [
        'pre_book_surcharge',
        'night_surcharge',
        'holiday_surcharge',
        'waiting_surcharge',
        'toll_surcharge',
        'is_active',
    ];

    protected $casts = [
        'id'                 => 'string',
        'pre_book_surcharge' => 'float',
        'night_surcharge'    => 'float',
        'holiday_surcharge'  => 'float',
        'waiting_surcharge'  => 'float',
        'toll_surcharge'     => 'float',
        'is_active'          => 'boolean',
    ];
}
