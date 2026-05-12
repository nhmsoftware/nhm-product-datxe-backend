<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use App\Modules\Finance\Model\Enums\RefundStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class ProcessRefundRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Admin check in middleware
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(RefundStatus::class)],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
