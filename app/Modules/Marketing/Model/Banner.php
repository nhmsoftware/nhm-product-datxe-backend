<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\Marketing\Model\Enums\MarketingItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $table = 'banners';

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'action_url',
        'order',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'order' => 'integer',
        'status' => MarketingItemStatus::class,
    ];
}
