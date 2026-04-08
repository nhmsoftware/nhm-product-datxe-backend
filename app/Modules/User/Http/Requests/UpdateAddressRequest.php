<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAddressRequest extends FormRequest
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
            'label' => 'sometimes|integer|in:1,2,3,4',
            'name' => 'nullable|string|max:100',
            'address_text' => 'sometimes|string|max:500',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lng' => 'sometimes|numeric|between:-180,180',
            'receiver_name' => 'nullable|string|max:100',
            'receiver_phone' => 'nullable|string|max:20',
            'note' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
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
            'label.in' => 'Tên địa chỉ không hợp lệ.',
            'address_text.max' => 'Địa chỉ chi tiết không được vượt quá 500 ký tự.',
            'lat.between' => 'Vĩ độ không hợp lệ.',
            'lng.between' => 'Kinh độ không hợp lệ.',
            'receiver_phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'note.max' => 'Ghi chú không được vượt quá 255 ký tự.',
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
