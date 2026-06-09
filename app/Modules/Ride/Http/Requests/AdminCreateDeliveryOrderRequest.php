<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminCreateDeliveryOrderRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_phone' => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'destination_address' => ['required', 'string', 'max:500'],
            'destination_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'goods_type' => ['required', 'string', 'max:100'],
            'goods_note' => ['nullable', 'string', 'max:500'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'driver_id' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'sender_name.required' => 'Vui lòng nhập người gửi.',
            'sender_phone.required' => 'Vui lòng nhập số điện thoại người gửi.',
            'sender_phone.regex' => 'Số điện thoại người gửi không hợp lệ.',
            'pickup_address.required' => 'Vui lòng nhập điểm lấy hàng.',
            'receiver_name.required' => 'Vui lòng nhập người nhận.',
            'receiver_phone.required' => 'Vui lòng nhập số điện thoại người nhận.',
            'receiver_phone.regex' => 'Số điện thoại người nhận không hợp lệ.',
            'destination_address.required' => 'Vui lòng nhập điểm giao hàng.',
            'goods_type.required' => 'Vui lòng nhập loại hàng hóa.',
            'total_price.required' => 'Vui lòng nhập tổng thanh toán.',
            'total_price.min' => 'Tổng thanh toán không hợp lệ.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $pickupAddress = trim((string) $this->input('pickup_address'));
            $destinationAddress = trim((string) $this->input('destination_address'));

            if ($pickupAddress !== '' && $destinationAddress !== '' && mb_strtolower($pickupAddress) === mb_strtolower($destinationAddress)) {
                $validator->errors()->add('destination_address', 'Điểm lấy hàng hoặc điểm giao hàng không hợp lệ.');
            }

            if ($this->filled('pickup_lat') xor $this->filled('pickup_lng')) {
                $validator->errors()->add('pickup_address', 'Điểm lấy hàng hoặc điểm giao hàng không hợp lệ.');
            }

            if ($this->filled('destination_lat') xor $this->filled('destination_lng')) {
                $validator->errors()->add('destination_address', 'Điểm lấy hàng hoặc điểm giao hàng không hợp lệ.');
            }

            if (
                $this->filled('pickup_lat') && $this->filled('pickup_lng')
                && $this->filled('destination_lat') && $this->filled('destination_lng')
            ) {
                $pLat = (float) $this->input('pickup_lat');
                $pLng = (float) $this->input('pickup_lng');
                $dLat = (float) $this->input('destination_lat');
                $dLng = (float) $this->input('destination_lng');

                if (abs($pLat - $dLat) < 0.000001 && abs($pLng - $dLng) < 0.000001) {
                    $validator->errors()->add('destination_address', 'Điểm lấy hàng hoặc điểm giao hàng không hợp lệ.');
                }
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
