<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-38: Validation cho yêu cầu chụp/tải ảnh xác nhận giao hàng.
 */
class CaptureDeliveryProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $hasPhoto = $this->hasFile('photo');

        return [
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'captured_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'captured_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'skip_reason'  => [$hasPhoto ? 'nullable' : 'required', 'string', 'in:customer_refused,device_error,other'],
            'note'         => [$hasPhoto ? 'nullable' : 'required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.image'          => 'Tệp tải lên phải là ảnh.',
            'photo.max'            => 'Ảnh không được vượt quá 10MB.',
            'skip_reason.required' => 'Vui lòng chọn lý do không thể chụp ảnh giao hàng (A3).',
            'note.required'        => 'Vui lòng nhập ghi chú khi không thể chụp ảnh.',
        ];
    }
}
