<?php

declare(strict_types=1);

namespace App\Modules\Food\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class FoodRating extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'food_order_id',
        'customer_id',
        'merchant_id',
        'rating',
        'comment',
        'food_quality_rating',
        'delivery_time_rating',
        'service_rating',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
        });
    }

    public function itemRatings(): HasMany
    {
        return $this->hasMany(FoodItemRating::class, 'food_rating_id');
    }
}
