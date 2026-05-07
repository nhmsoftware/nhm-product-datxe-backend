<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request validate cho hành động hủy chuyến xe (UC-15).
 */
final class CancelRideRequest extends FormRequest
{
    use HandleApi;
    /**
     * Xác định người dùng có quyền thực hiện request này hay không.
     */
    public function authorize(): bool
    {
        // Quyền sở hữu sẽ được check ở Service qua customer_id
        return true;
    }

    /**
     * Quy tắc validation cho request đầu vào.
     */
    public function rules(): array
    {
        return [
            'rideId' => ['bail', 'required', 'numeric', 'exists:rides,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Tùy chỉnh thông báo lỗi.
     */
    public function messages(): array
    {
        return [
            'rideId.required' => 'ID chuyến xe là bắt buộc.',
            'rideId.exists'   => 'Chuyến xe không tồn tại.',
            'reason.max'      => 'Lý do hủy không được vượt quá 255 ký tự.',
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
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
