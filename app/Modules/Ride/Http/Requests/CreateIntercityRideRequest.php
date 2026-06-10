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
            'pickup_lat'          => 'required|numeric|between:-90,90',
            'pickup_lng'          => 'required|numeric|between:-180,180',
            'destination_address' => 'required|string|max:255',
            'destination_lat'     => 'required|numeric|between:-90,90',
            'destination_lng'     => 'required|numeric|between:-180,180',
            'travel_date'         => 'required|date|after_or_equal:today',
            'travel_time'         => 'required|string|regex:/^\d{2}:\d{2}$/',
            'vehicle_type_id'     => 'required|integer|min:1',
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
            'vehicle_type_id.required'     => 'Vui lòng chọn loại xe.',
            'vehicle_type_id.min'          => 'Loại xe không hợp lệ cho chuyến đi tỉnh.',
        ];
    }

    /**
     * Kiểm tra bổ sung: Điểm đón và điểm đến không được trùng nhau.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $pLat = (float) $this->input('pickup_lat');
            $pLng = (float) $this->input('pickup_lng');
            $dLat = (float) $this->input('destination_lat');
            $dLng = (float) $this->input('destination_lng');

            if (abs($pLat - $dLat) < 0.000001 && abs($pLng - $dLng) < 0.000001) {
                $validator->errors()->add('destination_address', 'Điểm đón và điểm đến không được trùng nhau.');
            }
        });
    }
}
