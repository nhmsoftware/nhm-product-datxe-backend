<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * UC-30 Bước 1: Validate thông tin cá nhân + phương tiện → gửi OTP.
 * Không nhận file ở bước này — file upload ở bước 2 (submit).
 */
class RegisterDriverInitiateRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Thông tin cá nhân (UC-30 bước 3)
            'full_name'    => 'required|string|max:100',
            'phone'        => ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'citizen_id'   => ['required', 'string', 'regex:/^[0-9]{12}$/'],

            // Thông tin phương tiện (UC-30 bước 5)
            'vehicle_type'   => 'required|integer|in:1,2,3,4',
            'vehicle_name'   => 'required|string|max:255',
            'vehicle_color'  => 'required|integer|in:0,1,2,3,4,5,6,7,8,9',
            'vehicle_number' => 'required|string|max:20',
            'vehicle_year'   => 'required|integer|min:1990|max:' . now()->year,
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'    => 'Vui lòng nhập họ tên.',
            'full_name.max'         => 'Họ tên không được vượt quá 100 ký tự.',
            'phone.required'        => 'Vui lòng nhập số điện thoại.',
            'phone.regex'           => 'Số điện thoại không đúng định dạng.',
            'citizen_id.required'   => 'Vui lòng nhập số CCCD.',
            'citizen_id.regex'      => 'CCCD phải gồm đúng 12 chữ số.',

            'vehicle_type.required'   => 'Vui lòng chọn loại xe.',
            'vehicle_type.in'         => 'Loại xe không hợp lệ.',
            'vehicle_name.required'   => 'Vui lòng nhập tên xe.',
            'vehicle_color.required'  => 'Vui lòng chọn màu xe.',
            'vehicle_color.in'        => 'Màu xe không hợp lệ.',
            'vehicle_number.required' => 'Vui lòng nhập biển số xe.',
            'vehicle_year.required'   => 'Vui lòng nhập năm sản xuất.',
            'vehicle_year.integer'    => 'Năm sản xuất không hợp lệ.',
            'vehicle_year.min'        => 'Năm sản xuất phải từ 1990 trở về sau.',
            'vehicle_year.max'        => 'Năm sản xuất không được lớn hơn năm hiện tại.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
