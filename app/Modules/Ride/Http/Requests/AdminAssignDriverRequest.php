<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdminAssignDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ride_id'   => 'required|string',
            'driver_id' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'ride_id.required'   => 'Vui lòng chọn chuyến xe.',
            'driver_id.required' => 'Vui lòng chọn tài xế.',
        ];
    }
}
