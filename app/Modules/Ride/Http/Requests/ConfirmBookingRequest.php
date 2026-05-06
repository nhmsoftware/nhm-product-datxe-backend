<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * FormRequest cho UC-12: Xác nhận đặt xe.
 * Khách hàng chọn loại xe và gửi giá kỳ vọng để hệ thống kiểm tra chênh lệch.
 */
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
            'rideId'         => ['required', 'numeric', 'exists:rides,id'],
            'vehicle_type'   => ['required', 'integer', 'in:1,2,3,4'],
            'expected_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'rideId.required'         => 'ID chuyến xe là bắt buộc.',
            'rideId.exists'           => 'Chuyến xe không tồn tại.',
            'vehicle_type.required'   => 'Vui lòng chọn loại xe.',
            'vehicle_type.in'         => 'Loại xe không hợp lệ. Chọn: 1 (Xe máy), 2 (4 chỗ), 3 (7 chỗ), 4 (9 chỗ).',
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
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
