<?php

declare(strict_types=1);

namespace App\Modules\User\Model;

use App\Core\Traits\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class DriverGroup extends Model
{
    use HasBigIntId, SoftDeletes;

    protected $table = 'driver_groups';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the profiles belonging to this group.
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(DriverProfile::class, 'driver_group_id');
    }
}
