<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_token' => 'required|string',
            'user' => 'nullable|string',
            'device_id' => 'nullable|string|max:255',
            'device_token' => 'nullable|string',
            'device_type' => 'nullable|string|in:android,ios',
        ];
    }
}

