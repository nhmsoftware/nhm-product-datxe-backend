<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class EstimateRideOptionsRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'destination_lat' => ['required', 'numeric', 'between:-90,90'],
            'destination_lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $pickupLat = (float) $this->input('pickup_lat');
            $pickupLng = (float) $this->input('pickup_lng');
            $destinationLat = (float) $this->input('destination_lat');
            $destinationLng = (float) $this->input('destination_lng');

            if (abs($pickupLat - $destinationLat) < 0.000001 && abs($pickupLng - $destinationLng) < 0.000001) {
                $validator->errors()->add('destination_lat', 'Điểm đón và điểm đến không được trùng nhau.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
