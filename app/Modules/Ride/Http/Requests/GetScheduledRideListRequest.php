<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetScheduledRideListRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return $this->user()->isDriver();
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu lọc không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }

    public function rules(): array
    {
        return [
            'travel_date'      => 'nullable|date',
            'travel_time'      => 'nullable|string|regex:/^\d{2}:\d{2}$/',
            'pickup_area'      => 'nullable|string|max:100',
            'destination_area' => 'nullable|string|max:100',
            'ride_type'        => 'nullable|integer|in:1,2,3', // CITY, INTERCITY, AIRPORT
            'min_price'        => 'nullable|numeric|min:0',
            'max_price'        => 'nullable|numeric|min:0',
        ];
    }
}
