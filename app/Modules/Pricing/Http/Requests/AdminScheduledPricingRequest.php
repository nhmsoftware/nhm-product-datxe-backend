<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdminScheduledPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_price'           => 'required|numeric|min:0',
            'scheduled_surcharge'  => 'required|numeric|min:0',
            'intercity_base_price' => 'required|numeric|min:0',
            'airport_base_price'   => 'required|numeric|min:0',
            'dispatch_mode'        => 'required|integer|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'base_price.min'           => 'Giá cấu hình không hợp lệ.',
            'scheduled_surcharge.min'  => 'Giá cấu hình không hợp lệ.',
            'intercity_base_price.min' => 'Giá cấu hình không hợp lệ.',
            'airport_base_price.min'   => 'Giá cấu hình không hợp lệ.',
            'dispatch_mode.in'         => 'Chế độ phân phối không hợp lệ.',
        ];
    }
}
