<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StartRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Vĩ độ không được để trống.',
            'lng.required' => 'Kinh độ không được để trống.',
        ];
    }
}
