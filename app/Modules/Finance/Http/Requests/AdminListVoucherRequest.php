<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class AdminListVoucherRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Admin check handled by middleware
    }

    public function rules(): array
    {
        return [
            'code' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu tìm kiếm không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
