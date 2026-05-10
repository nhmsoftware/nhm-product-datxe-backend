<?php

declare(strict_types=1);

namespace App\Modules\Food\Model;

use Illuminate\Database\Eloquent\Model;

final class FoodItemRating extends Model
{
    protected $fillable = [
        'food_rating_id',
        'menu_item_id',
        'rating',
        'comment',
    ];
}
