<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ViewRewardHistoryRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'       => 'nullable|integer|in:1,2,3',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'                   => 'Loại giao dịch điểm không hợp lệ.',
            'start_date.date_format'    => 'Ngày bắt đầu không đúng định dạng YYYY-MM-DD.',
            'end_date.date_format'      => 'Ngày kết thúc không đúng định dạng YYYY-MM-DD.',
            'end_date.after_or_equal'   => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'per_page.min'              => 'Số lượng hiển thị trên trang phải lớn hơn 0.',
            'per_page.max'              => 'Số lượng hiển thị không được vượt quá 100.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
