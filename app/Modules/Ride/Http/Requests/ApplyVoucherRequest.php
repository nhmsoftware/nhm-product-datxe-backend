<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest cho UC-11: Áp dụng voucher vào chuyến đi.
 */
class ApplyVoucherRequest extends FormRequest
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
            // Mã voucher: bắt buộc, tối đa 50 ký tự
            'voucher_code' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'voucher_code.required' => 'Vui lòng nhập mã giảm giá.',
            'voucher_code.string'   => 'Mã giảm giá không hợp lệ.',
            'voucher_code.max'      => 'Mã giảm giá không được vượt quá 50 ký tự.',
        ];
    }
}
