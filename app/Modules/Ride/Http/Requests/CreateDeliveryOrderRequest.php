<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-25: Validation cho yêu cầu tạo đơn giao hàng.
 */
class CreateDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Điểm lấy hàng
            'pickup_address'      => ['required', 'string', 'max:500'],
            'pickup_lat'          => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng'          => ['required', 'numeric', 'between:-180,180'],

            // Điểm giao hàng
            'destination_address' => ['required', 'string', 'max:500'],
            'destination_lat'     => ['required', 'numeric', 'between:-90,90'],
            'destination_lng'     => ['required', 'numeric', 'between:-180,180'],

            // Loại xe giao hàng từ catalog động
            'vehicle_type_id'     => ['required', 'integer', 'min:1'],

            // Người gửi (A3)
            'sender_name'         => ['required', 'string', 'max:100'],
            'sender_phone'        => ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],

            // Người nhận (A4)
            'receiver_name'       => ['required', 'string', 'max:100'],
            'receiver_phone'      => ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],

            // Hàng hóa (A5)
            'goods_type'          => ['required', 'string', 'max:100'],
            'goods_weight'        => ['required', 'numeric', 'min:0.1', 'max:50'],
            'goods_note'          => ['nullable', 'string', 'max:500'],
            'is_fragile'          => ['nullable', 'boolean'],

            // Voucher (tuỳ chọn)
            'voucher_code'        => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_address.required'      => 'Vui lòng nhập điểm lấy hàng.',
            'destination_address.required' => 'Vui lòng nhập điểm giao hàng.',
            'vehicle_type_id.required'     => 'Vui lòng chọn loại xe.',
            'vehicle_type_id.min'          => 'Loại xe không hợp lệ.',
            'sender_name.required'         => 'Thông tin người gửi không hợp lệ: thiếu tên.',
            'sender_phone.required'        => 'Thông tin người gửi không hợp lệ: thiếu số điện thoại.',
            'sender_phone.regex'           => 'Thông tin người gửi không hợp lệ: sai định dạng SĐT.',
            'receiver_name.required'       => 'Thông tin người nhận không hợp lệ: thiếu tên.',
            'receiver_phone.required'      => 'Thông tin người nhận không hợp lệ: thiếu số điện thoại.',
            'receiver_phone.regex'         => 'Thông tin người nhận không hợp lệ: sai định dạng SĐT.',
            'goods_type.required'          => 'Thông tin hàng hóa không hợp lệ: thiếu loại hàng.',
            'goods_weight.required'        => 'Thông tin hàng hóa không hợp lệ: thiếu cân nặng.',
            'goods_weight.min'             => 'Thông tin hàng hóa không hợp lệ: cân nặng phải lớn hơn 0.',
            'goods_weight.max'             => 'Thông tin hàng hóa không hợp lệ: cân nặng tối đa 50kg.',
        ];
    }
}
