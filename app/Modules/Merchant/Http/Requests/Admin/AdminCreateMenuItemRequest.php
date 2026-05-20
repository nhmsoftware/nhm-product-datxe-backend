<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Requests\Admin;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

final class AdminCreateMenuItemRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:255'],
            'price'                  => ['required', 'numeric', 'min:0'],
            'category_name'          => ['required', 'string', 'max:255'],
            'category_id'            => ['nullable', 'string'],
            'description'            => ['nullable', 'string'],
            'image'                  => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            
            // Sizes validation
            'sizes'                  => ['nullable', 'array'],
            'sizes.*.name'           => ['required', 'string', 'max:255'],
            'sizes.*.price'          => ['required', 'numeric', 'min:0'],
            'sizes.*.is_default'     => ['nullable', 'boolean'],

            // Toppings validation
            'toppings'               => ['nullable', 'array'],
            'toppings.*.name'        => ['required', 'string', 'max:255'],
            'toppings.*.price'       => ['required', 'numeric', 'min:0'],
            'toppings.*.max_quantity'=> ['nullable', 'integer', 'min:1'],
            'toppings.*.is_required' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Vui lòng nhập tên món.',
            'price.required'         => 'Vui lòng nhập giá món.',
            'price.numeric'          => 'Giá không hợp lệ.',
            'category_name.required' => 'Vui lòng chọn hoặc nhập danh mục.',
            'image.image'            => 'File không phải là ảnh.',
            'image.max'              => 'Ảnh quá dung lượng (tối đa 2MB).',
        ];
    }
}
