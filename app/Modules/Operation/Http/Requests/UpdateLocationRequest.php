<?php

declare(strict_types=1);

namespace App\Modules\Operation\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request xác thực dữ liệu cập nhật tọa độ.
 */
final class UpdateLocationRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Vĩ độ không được để trống.',
            'lng.required' => 'Kinh độ không được để trống.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendError('Dữ liệu tọa độ không hợp lệ.', 422, $validator->errors()->toArray())
        );
    }
}
