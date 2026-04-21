<?php

declare(strict_types=1);

namespace App\Modules\Driver\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request xác thực cho việc nhấn nút "Xác nhận & sẵn sàng" (UC-41).
 */
final class ConfirmReadyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Phân quyền thực hiện trong Service
    }

    public function rules(): array
    {
        return [
            // rideId lấy từ route parameter
        ];
    }
}
