<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request xác nhận đón khách.
 */
final class PickupRideRequest extends BaseFormRequest
{
    use HandleApi;

    /**
     * Xác định quyền của user đối với request này.
     */
    public function authorize(): bool
    {
        return true; // Quyền sở hữu chuyến xe sẽ được kiểm tra ở Service
    }

    /**
     * Quy tắc validation.
     */
    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Custom thông báo lỗi.
     */
    public function messages(): array
    {
        return [
            'lat.required' => 'Vĩ độ không được để trống.',
            'lat.numeric'  => 'Vĩ độ phải là số.',
            'lng.required' => 'Kinh độ không được để trống.',
            'lng.numeric'  => 'Kinh độ phải là số.',
        ];
    }

    /**
     * Override failedValidation để trả về định dạng JSON chung của hệ thống.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
