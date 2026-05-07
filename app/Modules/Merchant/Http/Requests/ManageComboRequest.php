<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

final class ManageComboRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Middleware handles authentication
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:1',
            'image_path'     => 'nullable|string',
            'is_available'   => 'boolean',
            'order'          => 'integer',
            'items'          => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $ids = array_column($value, 'menu_item_id');
                    if (count($ids) !== count(array_unique($ids))) {
                        $fail('Món ăn này đã có trong combo.');
                    }
                },
            ],
            'items.*.menu_item_id' => 'required|exists:merchant_menu_items,id',
            'items.*.quantity'     => 'required|integer|min:0',
        ];
    }
}
