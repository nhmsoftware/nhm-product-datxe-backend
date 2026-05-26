<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetDriverRidesRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return $this->user()->isDriver();
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu yêu cầu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }

    public function rules(): array
    {
        return [
            'status'   => 'nullable|string|in:processing,completed,cancelled',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page'     => 'nullable|integer|min:1',
        ];
    }
}
