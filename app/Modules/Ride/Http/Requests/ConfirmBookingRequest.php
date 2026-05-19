<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * FormRequest cho UC-12: Xác nhận đặt xe.
 * Nhận trực tiếp thông tin chuyến đi để đặt xe.
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
            'pickup_address'      => ['required', 'string'],
            'pickup_lat'          => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng'          => ['required', 'numeric', 'between:-180,180'],
            'destination_address' => ['required', 'string'],
            'destination_lat'     => ['required', 'numeric', 'between:-90,90'],
            'destination_lng'     => ['required', 'numeric', 'between:-180,180'],
            'vehicle_type'        => ['required', 'integer', 'in:1,2,3,4'],
            'expected_price'      => ['required', 'numeric', 'min:0'],
            'voucher_code'        => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_address.required'      => 'Vui lòng nhập địa chỉ pickup.',
            'pickup_lat.required'          => 'Vui lòng nhập tọa độ pickup.',
            'pickup_lng.required'          => 'Vui lòng nhập tọa độ pickup.',
            'destination_address.required' => 'Vui lòng nhập địa chỉ destination.',
            'destination_lat.required'     => 'Vui lòng nhập tọa độ destination.',
            'destination_lng.required'     => 'Vui lòng nhập tọa độ destination.',
            'vehicle_type.required'        => 'Vui lòng chọn loại xe.',
            'vehicle_type.in'              => 'Vui lòng chọn loại xe hợp lệ. Chọn: 1 (Xe máy), 2 (4 chỗ), 3 (7 chỗ), 4 (9 chỗ).',
            'expected_price.required'      => 'Vui lòng cung cấp giá cước dự kiến để hệ thống kiểm tra.',
            'expected_price.numeric'       => 'Giá cước dự kiến phải là một số.',
            'expected_price.min'           => 'Giá cước dự kiến không hợp lệ.',
        ];
    }

    /**
     * Kiểm tra bổ sung: Điểm đón và điểm đến không được trùng nhau.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $pLat = (float) $this->input('pickup_lat');
            $pLng = (float) $this->input('pickup_lng');
            $dLat = (float) $this->input('destination_lat');
            $dLng = (float) $this->input('destination_lng');

            if (abs($pLat - $dLat) < 0.000001 && abs($pLng - $dLng) < 0.000001) {
                $validator->errors()->add('destination_address', 'Điểm đón và điểm đến không được trùng nhau.');
            }
        });
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
