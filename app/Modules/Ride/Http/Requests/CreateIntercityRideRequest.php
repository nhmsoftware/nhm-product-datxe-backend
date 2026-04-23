<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateIntercityRideRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
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
            'pickup_address'      => 'required|string|max:255',
            'pickup_lat'          => 'required|numeric',
            'pickup_lng'          => 'required|numeric',
            'destination_address' => 'required|string|max:255',
            'destination_lat'     => 'required|numeric',
            'destination_lng'     => 'required|numeric',
            'travel_date'         => 'required|date|after_or_equal:today',
            'travel_time'         => 'required|string|regex:/^\d{2}:\d{2}$/',
            'vehicle_type'        => 'required|integer|in:2,3,4,5', // 2: CAR_4_SEATS, 3: CAR_7_SEATS, 4: CAR_9_SEATS, 5: CAR_SHARED
            'voucher_code'        => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_address.required'      => 'Vui lòng nhập điểm đón.',
            'destination_address.required' => 'Vui lòng nhập điểm đến.',
            'travel_date.required'         => 'Vui lòng chọn ngày đi.',
            'travel_date.after_or_equal'   => 'Ngày đi không thể ở quá khứ.',
            'travel_time.required'         => 'Vui lòng chọn giờ đón.',
            'vehicle_type.required'        => 'Vui lòng chọn loại xe.',
            'vehicle_type.in'              => 'Loại xe không hợp lệ cho chuyến đi tỉnh.',
        ];
    }
}
