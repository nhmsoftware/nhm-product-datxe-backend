<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminCreateFoodOrderRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'merchant_id' => ['required', 'string', 'exists:merchant_profiles,id'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'delivery_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:500'],
            'subtotal_price' => ['required', 'numeric', 'min:0'],
            'delivery_fee' => ['required', 'numeric', 'min:0'],
            'service_fee' => ['required', 'numeric', 'min:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'driver_id' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'string', 'exists:merchant_menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.options' => ['nullable', 'array'],
            'items.*.options.*.name' => ['required', 'string'],
            'items.*.options.*.value' => ['required', 'string'],
            'items.*.options.*.price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Vui lòng nhập khách hàng.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại khách hàng.',
            'customer_phone.regex' => 'Số điện thoại khách hàng không hợp lệ.',
            'merchant_id.required' => 'Vui lòng chọn nhà hàng.',
            'merchant_id.exists' => 'Không tìm thấy nhà hàng.',
            'delivery_address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'subtotal_price.required' => 'Vui lòng nhập phí món ăn.',
            'delivery_fee.required' => 'Vui lòng nhập phí giao hàng.',
            'service_fee.required' => 'Vui lòng nhập phí dịch vụ.',
            'total_price.required' => 'Vui lòng nhập tổng thanh toán.',
            'items.required' => 'Vui lòng chọn món ăn.',
            'items.min' => 'Vui lòng chọn ít nhất một món ăn.',
            'items.*.menu_item_id.exists' => 'Món ăn hiện không khả dụng.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
