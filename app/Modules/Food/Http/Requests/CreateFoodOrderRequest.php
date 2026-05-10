<?php

declare(strict_types=1);

namespace App\Modules\Food\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateFoodOrderRequest',
    required: ['merchant_id', 'delivery_address', 'delivery_lat', 'delivery_lng', 'customer_phone', 'items'],
    properties: [
        new OA\Property(property: 'merchant_id', type: 'integer', example: 1),
        new OA\Property(property: 'delivery_address', type: 'string', example: '123 Nguyễn Trãi, Q1, HCM'),
        new OA\Property(property: 'delivery_lat', type: 'number', format: 'float', example: 10.762622),
        new OA\Property(property: 'delivery_lng', type: 'number', format: 'float', example: 106.660172),
        new OA\Property(property: 'customer_phone', type: 'string', example: '0901234567'),
        new OA\Property(property: 'notes', type: 'string', example: 'Giao lầu 3'),
        new OA\Property(property: 'voucher_code', type: 'string', example: 'KM50'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'menu_item_id', type: 'integer', example: 10),
                    new OA\Property(property: 'quantity', type: 'integer', example: 2),
                    new OA\Property(property: 'notes', type: 'string', example: 'Ít cay'),
                    new OA\Property(
                        property: 'options',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Size'),
                                new OA\Property(property: 'value', type: 'string', example: 'L'),
                                new OA\Property(property: 'price', type: 'number', example: 5000),
                            ]
                        )
                    )
                ]
            )
        )
    ]
)]
final class CreateFoodOrderRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => ['required', 'integer', 'exists:merchant_profiles,id'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'delivery_lat' => ['required', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['required', 'numeric', 'between:-180,180'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:merchant_menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.options' => ['nullable', 'array'],
            'items.*.options.*.name' => ['required', 'string'],
            'items.*.options.*.value' => ['required', 'string'],
            'items.*.options.*.price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Giỏ hàng trống.',
            'items.min' => 'Giỏ hàng trống.',
            'merchant_id.exists' => 'Cửa hàng không tồn tại.',
            'items.*.menu_item_id.exists' => 'Món ăn không tồn tại.',
        ];
    }
}
