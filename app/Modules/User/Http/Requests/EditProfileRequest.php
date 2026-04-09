<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Chuẩn bị dữ liệu để xác thực.
     */
    protected function prepareForValidation(): void
    {
        // Phối hợp dob vào birthday nếu birthday để trống
        if ($this->has('dob') && !$this->has('birthday')) {
            $this->merge([
                'birthday' => $this->input('dob'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Thông tin cơ bản (chung cho tất cả vai trò)
            'avatar' => 'nullable|string|max:500',
            'full_name' => 'nullable|string|max:100',
            'gender' => 'nullable|integer|in:1,2,3',
            'address' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'citizen_id' => 'nullable|string|max:20|regex:/^[0-9]{12}$/',

            // Customer-specific (nếu có)
            'birthday' => 'nullable|date|before:today',
            'dob' => 'nullable|date|before:today',

            // Driver-specific fields
            'vehicle_name' => 'nullable|string|max:255',
            'vehicle_type' => 'nullable|integer|min:1',
            'vehicle_color' => 'nullable|integer|min:1',
            'vehicle_number' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:50',
            'license_front_image' => 'nullable|string|max:500',
            'license_back_image' => 'nullable|string|max:500',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',

            // Merchant-specific fields
            'store_name' => 'nullable|string|max:255',
            'store_address' => 'nullable|string|max:500',
            'store_latitude' => 'nullable|numeric|between:-90,90',
            'store_longitude' => 'nullable|numeric|between:-180,180',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i|after:opening_time',
            'is_open' => 'nullable|boolean',
            'business_license' => 'nullable|string|max:50',
            'business_license_image' => 'nullable|string|max:500',
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
            'full_name.max' => 'Họ tên không được vượt quá 100 ký tự.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'email.email' => 'Email không đúng định dạng.',
            'citizen_id.regex' => 'Căn cước công dân phải có 12 chữ số.',
            'birthday.before' => 'Ngày sinh phải trước ngày hôm nay.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'citizen_id.max' => 'Căn cước công dân không được vượt quá 20 ký tự.'
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
