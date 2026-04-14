<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validate cho hành động hủy chuyến xe (UC-15).
 */
final class CancelRideRequest extends FormRequest
{
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
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Tùy chỉnh thông báo lỗi.
     */
    public function messages(): array
    {
        return [
            'reason.max' => 'Lý do hủy không được vượt quá 255 ký tự.',
        ];
    }
}
