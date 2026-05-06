<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * FormRequest cho UC-09: Lấy danh sách loại xe kèm giá ước tính.
 * Stateless — nhận tọa độ trực tiếp, không yêu cầu rideId.
 */
final class GetVehicleOptionsRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_lat'      => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng'      => ['required', 'numeric', 'between:-180,180'],
            'destination_lat' => ['required', 'numeric', 'between:-90,90'],
            'destination_lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_lat.required'      => 'Vĩ độ điểm đón là bắt buộc.',
            'pickup_lat.between'       => 'Vĩ độ điểm đón không hợp lệ.',
            'pickup_lng.required'      => 'Kinh độ điểm đón là bắt buộc.',
            'pickup_lng.between'       => 'Kinh độ điểm đón không hợp lệ.',
            'destination_lat.required' => 'Vĩ độ điểm đến là bắt buộc.',
            'destination_lat.between'  => 'Vĩ độ điểm đến không hợp lệ.',
            'destination_lng.required' => 'Kinh độ điểm đến là bắt buộc.',
            'destination_lng.between'  => 'Kinh độ điểm đến không hợp lệ.',
        ];
    }

    /**
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
