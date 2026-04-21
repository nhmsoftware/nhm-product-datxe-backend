<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * FormRequest cho UC-10: Xem chi tiết giá ước tính.
 */
final class GetPriceEstimateRequest extends FormRequest
{
    use HandleApi;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rideId' => ['required', 'numeric', 'exists:rides,id'],
        ];
    }

    /**
     * Đồng bộ hóa dữ liệu từ route vào request data để validate.
     */
    public function all($keys = null): array
    {
        $data = parent::all($keys);
        $data['rideId'] = $this->route('rideId');
        return $data;
    }

    /**
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
