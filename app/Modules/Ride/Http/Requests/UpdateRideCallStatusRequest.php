<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use App\Modules\Ride\Model\Enums\RideCallStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

final class UpdateRideCallStatusRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RideCallStatus::class)],
            'failure_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng cung cấp trạng thái cuộc gọi.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException($this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400));
    }
}
