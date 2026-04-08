<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Modules\User\Model\Enums\UserOtpType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends FormRequest
{

    /**
     * Xác định xem người dùng có được phép thực hiện yêu cầu này hay không.
     *
     * @return bool
     */
    public function authorize(): bool { return true; }

    /**
     * Định nghĩa quy tắc xác thực cho yêu cầu này.
     *
     * @return array
     */
    public function rules(): array
    {

        return [
            'phone' => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'otp'   => ['required', 'string', 'digits:6'],
            'type'  => ['required', Rule::enum(UserOtpType::class)],
            'device_id'    => ['nullable', 'string', 'max:255'],
            'device_token' => ['nullable', 'string', 'max:500'],
            'device_type'  => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Định nghĩa các thông báo xác thực cho yêu cầu này.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'otp.required' => 'Mã OTP không được để trống.',
            'otp.digits'   => 'Mã OTP phải gồm 6 chữ số.',
            'type.required' => 'Loại OTP không được để trống.',
            'type.enum'       => 'Loại OTP không hợp lệ.',
        ];
    }
}
