<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'otp' => 'required|string|size:6|regex:/^[0-9]+$/',
            'sensitive_data' => 'nullable|array',
            'sensitive_data.phone' => ['nullable', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'sensitive_data.email' => 'nullable|email|max:255',
            'sensitive_data.password' => 'nullable|string|min:8|max:50',
            'sensitive_data.device_id' => 'nullable|string|max:255',
            'sensitive_data.device_token' => 'nullable|string|max:500',
            'sensitive_data.device_type' => 'nullable|string|max:50',
            'sensitive_data.role' => 'nullable|integer|in:1,2,3',
            'sensitive_data.is_agree' => 'nullable|boolean',

        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp.required' => 'Vui lòng nhập mã OTP.',
            'otp.size' => 'Mã OTP phải có 6 chữ số.',
            'otp.regex' => 'Mã OTP chỉ được chứa chữ số.',
            'sensitive_data.email.email' => 'Email không đúng định dạng.',
            'sensitive_data.password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'sensitive_data.password.max' => 'Mật khẩu không được vượt quá 50 ký tự.',
            'sensitive_data.device_id.max' => 'ID thiết bị không được vượt quá 255 ký tự.',
            'sensitive_data.device_token.max' => 'Token thiết bị không được vượt quá 500 ký tự.',
            'sensitive_data.device_type.max' => 'Kiểu thiết bị không được vượt quá 50 ký tự.',
            'sensitive_data.role.in' => 'Vui lòng chọn vai trò hợp lệ.',
            'sensitive_data.is_agree.required' => 'Vui lòng chọn trạng thái đồng ý.',
            'sensitive_data.is_agree.boolean' => 'Vui lòng chọn trạng thái đồng ý hợp lệ.',
            'sensitive_data.phone.regex' => 'Số điện thoại không đúng định dạng (VD: 0912345678).',
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
