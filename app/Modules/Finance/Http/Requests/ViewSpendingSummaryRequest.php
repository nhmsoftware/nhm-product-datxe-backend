<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ViewSpendingSummaryRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'range'      => 'required|string|in:day,week,month,custom',
            'start_date' => 'required_if:range,custom|date_format:Y-m-d|nullable',
            'end_date'   => 'required_if:range,custom|date_format:Y-m-d|nullable|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'range.required' => 'Vui lòng chọn loại bộ lọc thời gian.',
            'range.in'       => 'Loại bộ lọc không hợp lệ (day, week, month, custom).',
            'start_date.required_if' => 'Vui lòng chọn ngày bắt đầu cho khoảng thời gian tùy chọn.',
            'end_date.required_if'   => 'Vui lòng chọn ngày kết thúc cho khoảng thời gian tùy chọn.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 422)
        );
    }
}
