<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Modules\User\Model\Enums\UserOtpType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendOtpRequest extends FormRequest
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
            'type' => ['required', Rule::enum(UserOtpType::class)],
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
            'phone.required' => 'Số điện thoại không được để trống.',
            'phone.regex'    => 'Số điện thoại không đúng định dạng.',
            'type.required'  => 'Loại OTP không được để trống.',
            'type.enum'        => 'Loại OTP không hợp lệ.',
        ];
    }
}
