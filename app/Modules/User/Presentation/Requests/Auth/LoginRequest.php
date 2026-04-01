<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'        => ['required', 'string'],
            'password'     => ['required', 'string'],
            'device_id'    => ['nullable', 'string', 'max:255'],
            'device_token' => ['nullable', 'string', 'max:500'],
            'device_type'  => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'    => 'Số điện thoại không được để trống.',
            'password.required' => 'Mật khẩu không được để trống.',
        ];
    }
}
