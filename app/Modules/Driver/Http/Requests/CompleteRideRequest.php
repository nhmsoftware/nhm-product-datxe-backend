<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class CompleteRideRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rideId' => ['required', 'string', 'exists:rides,id'],
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

    public function messages(): array
    {
        return [
            'rideId.required' => 'ID chuyến xe là bắt buộc.',
            'rideId.exists'   => 'Chuyến xe không tồn tại.',
            'lat.required'    => 'Vĩ độ không được để trống.',
            'lng.required'    => 'Kinh độ không được để trống.',
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
