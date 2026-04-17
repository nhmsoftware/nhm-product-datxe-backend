<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AcceptOrderRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_lat' => 'required|numeric',
            'current_lng' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'current_lat.required' => 'Vui lòng cung cấp vĩ độ hiện tại.',
            'current_lng.required' => 'Vui lòng cung cấp kinh độ hiện tại.',
            'current_lat.numeric'  => 'Vĩ độ không hợp lệ.',
            'current_lng.numeric'  => 'Kinh độ không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
