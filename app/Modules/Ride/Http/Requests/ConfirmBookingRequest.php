<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class ConfirmBookingRequest extends FormRequest
{
    use HandleApi;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rideId'         => ['required', 'string', 'exists:rides,id'],
            'expected_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'rideId.required'         => 'ID chuyến xe là bắt buộc.',
            'rideId.exists'           => 'Chuyến xe không tồn tại.',
            'expected_price.required' => 'Vui lòng cung cấp giá cước dự kiến để hệ thống kiểm tra.',
            'expected_price.numeric'  => 'Giá cước dự kiến phải là một số.',
            'expected_price.min'      => 'Giá cước dự kiến không hợp lệ.',
        ];
    }

    /**
     * Đồng bộ hóa dữ liệu từ route vào request data để validate.
     */
    public function all($keys = null): array
    {
        $data = parent::all($keys);
        $data['rideId'] = $this->route('rideId');
        return $data;
    }

    /**
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
