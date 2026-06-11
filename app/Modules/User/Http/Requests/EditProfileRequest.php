<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Thông tin cơ bản (chung cho tất cả vai trò)
            'avatar' => 'nullable|string|max:500',
            'full_name' => 'required|string|max:100',
            'gender' => 'nullable|integer|in:1,2,3',
            'address' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => ['required', 'string', 'regex:/^0[35789][0-9]{8}$/'],
            'citizen_id' => 'nullable|string|max:20|regex:/^[0-9]{12}$/',

            // Customer-specific (nếu có)
            'birthday' => [
                'nullable',
                'date',
                'before:today',
                'after:' . today()->subYears(100)->format('Y-m-d'),
            ],

            // Driver-specific fields
            'vehicle_name' => 'nullable|string|max:255',
            'vehicle_type_id' => 'nullable|integer|min:1',
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
            // full_name
            'full_name.required' => 'Họ tên không được để trống.',
            'full_name.max'      => 'Họ tên không được vượt quá 100 ký tự.',

            // phone
            'phone.required' => 'Số điện thoại không được để trống.',
            'phone.regex'    => 'Số điện thoại không đúng định dạng (VD: 0912345678).',

            // email
            'email.email' => 'Email không đúng định dạng.',

            // citizen_id
            'citizen_id.regex' => 'Căn cước công dân phải có 12 chữ số.',

            // birthday
            'birthday.before' => 'Ngày sinh phải trước ngày hôm nay.',
            'birthday.after'  => 'Tuổi không được vượt quá 100 tuổi.',

            // gender
            'gender.in' => 'Giới tính không hợp lệ.',
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
