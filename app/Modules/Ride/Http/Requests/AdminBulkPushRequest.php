<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdminBulkPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ride_ids'   => 'required|array',
            'ride_ids.*' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'ride_ids.required' => 'Vui lòng chọn ít nhất một chuyến xe.',
        ];
    }
}
