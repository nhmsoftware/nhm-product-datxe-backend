<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

class RegisterMerchantRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'               => ['required', 'string', 'max:255'],
            'phone'                   => ['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'],
            'citizen_id'              => ['required', 'string', 'max:20'],
            'store_name'              => ['required', 'string', 'max:255'],
            'store_address'           => ['required', 'string', 'max:500'],
            'lat'                     => ['required', 'numeric', 'between:-90,90'],
            'lng'                     => ['required', 'numeric', 'between:-180,180'],
            'business_type'           => ['required', 'string', 'max:100'],
            'citizen_id_image'        => ['required', 'file', 'image', 'max:5120'],
            'business_license_image'  => ['nullable', 'file', 'image', 'max:5120'],
            'store_image'             => ['required', 'file', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'        => 'Vui lòng nhập họ tên.',
            'phone.required'            => 'Vui lòng nhập số điện thoại.',
            'citizen_id.required'       => 'Vui lòng nhập CCCD.',
            'store_name.required'       => 'Vui lòng nhập tên cửa hàng.',
            'store_address.required'    => 'Vui lòng nhập địa chỉ cửa hàng.',
            'lat.required'              => 'Vui lòng nhập vĩ độ.',
            'lat.numeric'               => 'Vĩ độ phải là số hợp lệ.',
            'lat.between'               => 'Vĩ độ phải nằm trong khoảng từ -90 đến 90.',
            'lng.required'              => 'Vui lòng nhập kinh độ.',
            'lng.numeric'               => 'Kinh độ phải là số hợp lệ.',
            'lng.between'               => 'Kinh độ phải nằm trong khoảng từ -180 đến 180.',
            'business_type.required'    => 'Vui lòng nhập loại hình kinh doanh.',
            'citizen_id_image.required' => 'Vui lòng tải lên ảnh CCCD.',
            'store_image.required'      => 'Vui lòng tải lên ảnh cửa hàng.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Thông tin không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
