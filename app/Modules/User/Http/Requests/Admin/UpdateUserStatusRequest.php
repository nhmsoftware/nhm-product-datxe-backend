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
            'is_active'       => 'required|boolean',
            'reason'          => 'required_if:is_active,false|nullable|string|max:255',
            'locked_days'     => 'nullable|integer|min:2',
            'lock_expired_at' => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required'       => 'Vui lòng chọn trạng thái hoạt động.',
            'is_active.boolean'        => 'Trạng thái không hợp lệ.',
            'reason.required_if'       => 'Vui lòng nhập lý do khóa tài khoản để thông báo cho khách hàng.',
            'reason.string'            => 'Lý do khóa phải là chuỗi ký tự.',
            'reason.max'               => 'Lý do khóa quá dài (tối đa 255 ký tự).',
            'locked_days.integer'      => 'Số ngày khóa phải là số nguyên.',
            'locked_days.min'          => 'Số ngày khóa không hợp lệ.',
            'lock_expired_at.date'     => 'Ngày hết hạn không đúng định dạng.',
            'lock_expired_at.after'    => 'Ngày hết hạn khóa phải sau ngày hôm nay.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
