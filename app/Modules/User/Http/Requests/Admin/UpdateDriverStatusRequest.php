<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDriverStatusRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'is_active' => 'required|boolean',
        ];

        // Nếu khóa tài khoản
        if ($this->boolean('is_active') === false) {
            $rules['lock_reason'] = 'required|string|max:500';
            $rules['locked_days'] = 'nullable|integer|min:1';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'lock_reason.required' => 'Vui lòng nhập lý do khóa tài khoản.',
            'locked_days.integer'  => 'Số ngày khóa không hợp lệ.',
            'locked_days.min'      => 'Số ngày khóa không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
