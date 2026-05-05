<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserStatusRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active'   => 'required|boolean',
            'reason'      => 'required_if:is_active,false|nullable|string|max:255',
            'locked_days' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required'   => 'Trạng thái hoạt động là bắt buộc.',
            'is_active.boolean'    => 'Trạng thái hoạt động phải là kiểu boolean.',
            'reason.required_if'   => 'Vui lòng nhập lý do khóa tài khoản.',
            'reason.string'        => 'Lý do khóa phải là chuỗi ký tự.',
            'reason.max'           => 'Lý do khóa không được vượt quá 255 ký tự.',
            'locked_days.integer'  => 'Số ngày khóa phải là số nguyên.',
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
