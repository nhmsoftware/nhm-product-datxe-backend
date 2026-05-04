<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class DriverCancelRideRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return $this->user()->isDriver();
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:5|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng nhập lý do hủy chuyến.',
            'reason.min' => 'Lý do hủy chuyến phải có ít nhất 5 ký tự.',
        ];
    }
}
