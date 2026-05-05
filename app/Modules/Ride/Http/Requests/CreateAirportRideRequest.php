<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateAirportRideRequest extends FormRequest
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
            'pickup_lat'          => 'required|numeric|between:-90,90',
            'pickup_lng'          => 'required|numeric|between:-180,180',
            'destination_address' => 'required|string|max:255',
            'destination_lat'     => 'required|numeric|between:-90,90',
            'destination_lng'     => 'required|numeric|between:-180,180',
            'travel_date'         => 'required|date|after_or_equal:today',
            'travel_time'         => 'required|string|regex:/^\d{2}:\d{2}$/',
            'vehicle_type'        => 'required|integer|in:1,2,3,4', // BIKE, CAR_4_SEATS, CAR_7_SEATS, CAR_9_SEATS
            'airport_id'          => 'required|integer|exists:airports,id',
            'airport_direction'   => 'required|integer|in:1,2', // 1: To Airport, 2: From Airport
            'voucher_code'        => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'airport_id.exists' => 'Sân bay không hợp lệ hoặc không được hỗ trợ.',
            'travel_date.after_or_equal' => 'Ngày đi không thể ở quá khứ.',
            'airport_direction.in' => 'Chiều đi không hợp lệ.',
        ];
    }
}
