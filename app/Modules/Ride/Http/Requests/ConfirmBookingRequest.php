<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expected_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'expected_price.required' => 'Vui lòng cung cấp giá cước dự kiến để hệ thống kiểm tra.',
            'expected_price.numeric'  => 'Giá cước dự kiến phải là một số.',
            'expected_price.min'      => 'Giá cước dự kiến không hợp lệ.',
        ];
    }
}
