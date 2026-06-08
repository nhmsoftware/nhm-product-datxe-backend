<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Requests\Admin;

use App\Core\Traits\HandleApi;
use App\Modules\Merchant\Model\Enums\MerchantBusinessType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

final class UpdateMerchantRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'store_name' => ['required', 'string', 'max:255'],
            'store_address' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'business_type' => ['nullable', 'integer', Rule::in(MerchantBusinessType::values())],
            'business_license' => ['nullable', 'string', 'max:100'],
            'business_license_image' => ['nullable', 'file', 'image', 'max:5120'],
            'store_image' => ['nullable', 'file', 'image', 'max:5120'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i', 'after:opening_time'],
            'status' => ['nullable', 'integer', 'in:1,2,3'],
            'is_active' => ['nullable', 'boolean'],
            'lock_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_name.required' => 'Vui lòng nhập họ và tên chủ sở hữu.',
            'phone.required' => 'Vui lòng nhập số điện thoại chủ sở hữu.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'email.email' => 'Email không đúng định dạng.',
            'store_name.required' => 'Vui lòng nhập tên cửa hàng.',
            'store_address.required' => 'Vui lòng nhập địa chỉ cửa hàng.',
            'latitude.between' => 'Địa chỉ cửa hàng không hợp lệ.',
            'longitude.between' => 'Địa chỉ cửa hàng không hợp lệ.',
            'business_type.in' => 'Loại hình Merchant không hợp lệ.',
            'closing_time.after' => 'Giờ đóng cửa phải sau giờ mở cửa.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
