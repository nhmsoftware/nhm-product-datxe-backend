<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

final class AdminPaymentMethodRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $id = $this->route('id');

        return [
            'type'         => $isUpdate ? 'sometimes|string|in:e_wallet,bank_card,bank_transfer'
                                        : 'required|string|in:e_wallet,bank_card,bank_transfer',
            'code'         => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('payment_methods', 'code')->ignore($id)->whereNull('deleted_at'),
            ],
            'name'         => $isUpdate ? 'sometimes|string|max:100' : 'required|string|max:100',
            'is_active'    => 'sometimes|boolean',
            'min_amount'   => 'sometimes|numeric|min:0',
            'max_amount'   => 'sometimes|numeric|min:1',
            
            // Direct Transfer (bank_transfer) Configuration
            'transfer_info'=> 'sometimes|nullable|array',
            'transfer_info.bank_name'       => [
                $isUpdate ? 'sometimes' : Rule::requiredIf(fn() => $this->input('type') === 'bank_transfer'),
                'string',
                'max:100'
            ],
            'transfer_info.account_number'  => [
                $isUpdate ? 'sometimes' : Rule::requiredIf(fn() => $this->input('type') === 'bank_transfer'),
                'string',
                'max:50'
            ],
            'transfer_info.account_name'    => [
                $isUpdate ? 'sometimes' : Rule::requiredIf(fn() => $this->input('type') === 'bank_transfer'),
                'string',
                'max:100'
            ],
            'transfer_info.bank_code'       => 'sometimes|string|max:20',
            'transfer_info.branch'          => 'sometimes|nullable|string|max:100',
            'transfer_info.qr_url'          => 'sometimes|nullable|url',
            'transfer_info.default_content' => 'sometimes|nullable|string|max:255',
            'transfer_info.content_syntax'  => [
                $isUpdate ? 'sometimes' : Rule::requiredIf(fn() => $this->input('type') === 'bank_transfer'),
                'string',
                'max:255'
            ],
            'transfer_info.auto_reconciliation' => 'sometimes|boolean',

            // E-Wallet & Bank Card Configuration (stored in metadata)
            'metadata'     => 'sometimes|nullable|array',
            'metadata.merchant_id'  => [
                $isUpdate ? 'sometimes' : Rule::requiredIf(fn() => in_array($this->input('type'), ['e_wallet', 'bank_card'])),
                'string',
                'max:100'
            ],
            'metadata.api_key'      => 'sometimes|nullable|string|max:255',
            'metadata.secret_key'   => 'sometimes|nullable|string|max:255',
            'metadata.endpoint'     => [
                $isUpdate ? 'sometimes' : Rule::requiredIf(fn() => in_array($this->input('type'), ['e_wallet', 'bank_card'])),
                'nullable',
                'url',
                'max:255'
            ],
            'metadata.transaction_fee' => 'sometimes|nullable|numeric|min:0',
            'metadata.internal_note'   => 'sometimes|nullable|string|max:1000',
            'metadata.require_otp'     => 'sometimes|nullable|boolean',

            'icon_url'     => 'sometimes|nullable|url',
            'sort_order'   => 'sometimes|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'      => 'Loại phương thức không hợp lệ. Chấp nhận: e_wallet, bank_card, bank_transfer.',
            'code.unique'  => 'Mã phương thức này đã tồn tại.',
            'max_amount.min' => 'Số tiền tối đa phải lớn hơn 0.',
            'metadata.endpoint.url' => 'Thông tin kết nối không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
