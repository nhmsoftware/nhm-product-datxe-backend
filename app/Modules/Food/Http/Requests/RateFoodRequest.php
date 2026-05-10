<?php

declare(strict_types=1);

namespace App\Modules\Food\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RateFoodRequest',
    required: ['rating'],
    properties: [
        new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5, example: 5),
        new OA\Property(property: 'comment', type: 'string', maxLength: 1000, example: 'Món ăn rất ngon, giao hàng nhanh.'),
        new OA\Property(property: 'food_quality_rating', type: 'integer', minimum: 1, maximum: 5, example: 5),
        new OA\Property(property: 'delivery_time_rating', type: 'integer', minimum: 1, maximum: 5, example: 4),
        new OA\Property(property: 'service_rating', type: 'integer', minimum: 1, maximum: 5, example: 5),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'menu_item_id', type: 'integer', example: 10),
                    new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5, example: 5),
                    new OA\Property(property: 'comment', type: 'string', maxLength: 255, example: 'Rất đậm đà.')
                ]
            )
        )
    ]
)]
final class RateFoodRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'food_quality_rating' => ['nullable', 'integer', 'between:1,5'],
            'delivery_time_rating' => ['nullable', 'integer', 'between:1,5'],
            'service_rating' => ['nullable', 'integer', 'between:1,5'],
            'items' => ['nullable', 'array'],
            'items.*.menu_item_id' => ['required_with:items', 'integer', 'exists:merchant_menu_items,id'],
            'items.*.rating' => ['required_with:items', 'integer', 'between:1,5'],
            'items.*.comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}
