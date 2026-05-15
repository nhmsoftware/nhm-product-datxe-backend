<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDeviceTokenRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id'    => 'required|string',
            'device_token' => 'required|string',
            'device_type'  => 'nullable|string',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400));
    }
}
