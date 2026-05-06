<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * FormRequest cho UC-12: Xác nhận đặt xe (Book a Ride).
 * Nhận toàn bộ thông tin chuyến đi — không yêu cầu draft trước.
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
            // Điểm đón
            'pickup_address'      => ['required', 'string', 'max:500'],
            'pickup_lat'          => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng'          => ['required', 'numeric', 'between:-180,180'],
            // Điểm đến
            'destination_address' => ['required', 'string', 'max:500'],
            'destination_lat'     => ['required', 'numeric', 'between:-90,90'],
            'destination_lng'     => ['required', 'numeric', 'between:-180,180'],
            // Loại xe: 1=Bike, 2=Car4, 3=Car7, 4=Car9
            'vehicle_type'        => ['required', 'integer', 'in:1,2,3,4'],
            // Giá kỳ vọng từ UC-09 để hệ thống kiểm tra chênh lệch
            'expected_price'      => ['required', 'numeric', 'min:0'],
            // Danh sách voucher (tùy chọn)
            'voucher_codes'       => ['sometimes', 'array', 'max:5'],
            'voucher_codes.*'     => ['string', 'max:50'],
            // Ghi chú chuyến đi (tùy chọn)
            'note'                => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_address.required'      => 'Địa chỉ đón là bắt buộc.',
            'pickup_lat.required'          => 'Vĩ độ điểm đón là bắt buộc.',
            'pickup_lng.required'          => 'Kinh độ điểm đón là bắt buộc.',
            'destination_address.required' => 'Địa chỉ đến là bắt buộc.',
            'destination_lat.required'     => 'Vĩ độ điểm đến là bắt buộc.',
            'destination_lng.required'     => 'Kinh độ điểm đến là bắt buộc.',
            'vehicle_type.required'        => 'Vui lòng chọn loại xe.',
            'vehicle_type.in'              => 'Loại xe không hợp lệ. Chọn: 1 (Xe máy), 2 (4 chỗ), 3 (7 chỗ), 4 (9 chỗ).',
            'expected_price.required'      => 'Giá kỳ vọng là bắt buộc để kiểm tra chênh lệch giá.',
            'expected_price.min'           => 'Giá kỳ vọng không hợp lệ.',
            'voucher_codes.array'          => 'Danh sách voucher không hợp lệ.',
            'voucher_codes.max'            => 'Chỉ được áp dụng tối đa 5 voucher.',
            'voucher_codes.*.string'       => 'Mã voucher không hợp lệ.',
        ];
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
