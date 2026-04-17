<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ToggleOnlineStatusRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_online'   => 'required|boolean',
            'current_lat' => 'nullable|required_if:is_online,true|numeric|between:-90,90',
            'current_lng' => 'nullable|required_if:is_online,true|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'is_online.required'       => 'Vui lòng xác định trạng thái Online/Offline.',
            'is_online.boolean'        => 'Trạng thái Online/Offline phải là boolean.',
            'current_lat.required_if'  => 'Vui lòng cung cấp vĩ độ khi bật Online.',
            'current_lat.numeric'      => 'Vĩ độ phải là một số.',
            'current_lat.between'      => 'Vĩ độ không hợp lệ (-90 đến 90).',
            'current_lng.required_if'  => 'Vui lòng cung cấp kinh độ khi bật Online.',
            'current_lng.numeric'      => 'Kinh độ phải là một số.',
            'current_lng.between'      => 'Kinh độ không hợp lệ (-180 đến 180).',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu trạng thái không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
