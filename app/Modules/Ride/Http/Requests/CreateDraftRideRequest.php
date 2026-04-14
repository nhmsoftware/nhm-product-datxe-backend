<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateDraftRideRequest extends FormRequest
{
    use HandleApi;

    /**
     * Xác định xem người dùng có quyền thực hiện request này không (Authorization).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Khai báo các quy tắc kiểm tra dữ liệu đầu vào.
     */
    public function rules(): array
    {
        return [
            'pickup_address' => 'required|string',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'destination_address' => 'required|string',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
            'vehicle_type' => 'required|integer|in:1,2,3,4',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'pickup_address.required' => 'Vui lòng nhập địa chỉ.pickup.',
            'pickup_lat.required' => 'Vui lòng nhập độ độ.pickup.',
            'pickup_lng.required' => 'Vui lòng nhập độ độ.pickup.',
            'destination_address.required' => 'Vui lòng nhập địa chỉ.destination.',
            'destination_lat.required' => 'Vui lòng nhập tọa độ destination.',
            'destination_lng.required' => 'Vui lòng nhập tọa độ destination.',
            'vehicle_type.required' => 'Vui lòng chọn loại xe.',
            'vehicle_type.in' => 'Vui lòng chọn loại xe hợp lệ.',
        ];
    }

    /**
     * Xử lý lỗi validation để trả về định dạng JSON chung của hệ thống thay vì redirect.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400));
    }
}
