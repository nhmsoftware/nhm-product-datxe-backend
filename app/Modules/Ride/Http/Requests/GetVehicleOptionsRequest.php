<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest cho UC-09: Lấy danh sách xe khả dụng.
 * Yêu cầu ride_id để lấy đúng thông tin khoảng cách từ draft đã tạo.
 */
class GetVehicleOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function rules(): array
    {
        return [
            // route parameter {rideId} được validate ở controller
        ];
    }
}
