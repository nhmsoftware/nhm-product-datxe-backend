<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * FormRequest cho UC-11: Áp dụng voucher vào chuyến đi.
 */
class ApplyVoucherRequest extends FormRequest
{
    use HandleApi;

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
            'rideId'      => ['required', 'numeric', 'exists:rides,id'],
            'voucher_code' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rideId.required'       => 'ID chuyến xe là bắt buộc.',
            'rideId.exists'         => 'Chuyến xe không tồn tại.',
            'voucher_code.required' => 'Vui lòng nhập mã giảm giá.',
            'voucher_code.string'   => 'Mã giảm giá không hợp lệ.',
            'voucher_code.max'      => 'Mã giảm giá không được vượt quá 50 ký tự.',
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
