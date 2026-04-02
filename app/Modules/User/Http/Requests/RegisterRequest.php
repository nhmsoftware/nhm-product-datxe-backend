<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests;

use App\Modules\User\Model\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'        => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'otp'          => ['required', 'string', 'size:6'],
            'full_name'    => ['required', 'string', 'max:100'],
            'role'         => ['sometimes', 'integer', 'in:' . Rule::enum(UserRole::class)],
            'device_id'    => ['nullable', 'string', 'max:255'],
            'device_token' => ['nullable', 'string', 'max:500'],
            'device_type'  => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'    => 'Số điện thoại không được để trống.',
            'phone.regex'       => 'Số điện thoại không đúng định dạng Việt Nam.',
        ];
    }
}
