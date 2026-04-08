<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleLoginRequest extends FormRequest
{
    /**
     * Xác định xem người dùng có được phép thực hiện yêu cầu này hay không.
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Định nghĩa quy tắc xác thực cho yêu cầu này.
     * @return array
     */
    public function rules(): array
    {
        return [
            'id_token' => 'required|string',
            'device_id' => 'nullable|string|max:255',
            'device_token' => 'nullable|string',
            'device_type' => 'nullable|string|in:android,ios',
        ];
    }
}

