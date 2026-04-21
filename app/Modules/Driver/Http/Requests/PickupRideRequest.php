<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request xác nhận đón khách.
 */
final class PickupRideRequest extends FormRequest
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
            'rideId' => ['required', 'numeric', 'exists:rides,id'],
            'lat'    => ['required', 'numeric', 'between:-90,90'],
            'lng'    => ['required', 'numeric', 'between:-180,180'],
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

    /**
     * Custom thông báo lỗi.
     */
    public function messages(): array
    {
        return [
            'rideId.required' => 'ID chuyến xe là bắt buộc.',
            'rideId.exists'   => 'Chuyến xe không tồn tại.',
            'lat.required'    => 'Vĩ độ không được để trống.',
            'lat.numeric'     => 'Vĩ độ phải là số.',
            'lng.required'    => 'Kinh độ không được để trống.',
            'lng.numeric'     => 'Kinh độ phải là số.',
        ];
    }

    /**
     * Override failedValidation để trả về định dạng JSON chung của hệ thống.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 422)
        );
    }
}
