<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Modules\Ride\Model\Enums\RideCancelReason;
use Illuminate\Validation\Rules\Enum;

class CancelOrderRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rideId'      => ['required', 'numeric', 'exists:rides,id'],
            'reason_id'   => ['required', 'integer', new Enum(RideCancelReason::class)],
            'current_lat' => 'nullable|numeric',
            'current_lng' => 'nullable|numeric',
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

    public function messages(): array
    {
        return [
            'rideId.required'    => 'ID chuyến xe là bắt buộc.',
            'rideId.exists'      => 'Chuyến xe không tồn tại.',
            'reason_id.required' => 'Vui lòng chọn lý do hủy.',
            'reason_id.integer'  => 'Lý do hủy không hợp lệ.',
            'reason_id.Illuminate\Validation\Rules\Enum' => 'Lý do hủy không tồn tại.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
