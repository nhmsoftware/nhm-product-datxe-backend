<?php

declare(strict_types=1);

namespace App\Modules\Chauffeur\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request validation cho nghiệp vụ đặt Lái hộ (UC-124).
 */
class BookChauffeurRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_address'      => 'required|string',
            'pickup_lat'          => 'required|numeric|between:-90,90',
            'pickup_lng'          => 'required|numeric|between:-180,180',
            'destination_address' => 'required|string',
            'destination_lat'     => 'required|numeric|between:-90,90',
            'destination_lng'     => 'required|numeric|between:-180,180',
            'license_plate'       => 'required|string|max:20',
            'car_type'            => 'required|string|max:50',
            'car_brand'           => 'required|string|max:50',
            'car_color'           => 'required|string|max:30',
            'pickup_time'         => 'nullable|date_format:Y-m-d H:i:s',
            'voucher_code'        => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'license_plate.required' => 'Vui lòng nhập biển số xe.',
            'car_type.required'      => 'Vui lòng nhập loại xe.',
            'car_brand.required'     => 'Vui lòng nhập hãng xe.',
            'car_color.required'     => 'Vui lòng nhập màu xe.',
            'pickup_time.date_format' => 'Thời gian đón không đúng định dạng Y-m-d H:i:s.',
        ];
    }

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

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400));
    }
}
