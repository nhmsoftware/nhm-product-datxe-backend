<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class RespondRideCancellationRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rideId'    => 'required|string|exists:rides,id',
            'agreement' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'rideId.required'    => 'ID chuyến xe là bắt buộc.',
            'rideId.exists'      => 'Chuyến xe không tồn tại.',
            'agreement.required' => 'Vui lòng cung cấp phản hồi đồng ý hoặc từ chối.',
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
