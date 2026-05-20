<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\User\Model\User;
use App\Modules\User\Model\MerchantProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantMenuEditLog extends Model
{
    use HasBigIntId;

    protected $table = 'merchant_menu_edit_logs';

    protected $fillable = [
        'merchant_profile_id',
        'actor_id',
        'action',
        'description',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'id'                  => 'string',
        'merchant_profile_id' => 'string',
        'actor_id'            => 'string',
        'old_values'          => 'array',
        'new_values'          => 'array',
    ];

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_profile_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
