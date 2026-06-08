<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests\Admin;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class UpdateCustomerRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|integer|in:1,2,3',
            'birthday' => [
                'nullable',
                'date',
                'before:today',
                'after:' . today()->subYears(100)->format('Y-m-d'),
            ],
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.max' => 'Họ và tên không được vượt quá 100 ký tự.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'email.email' => 'Email không đúng định dạng.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'birthday.date' => 'Ngày sinh không đúng định dạng.',
            'birthday.before' => 'Ngày sinh phải trước ngày hôm nay.',
            'birthday.after' => 'Tuổi không được vượt quá 100 tuổi.',
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự.',
            'is_active.boolean' => 'Trạng thái tài khoản không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
