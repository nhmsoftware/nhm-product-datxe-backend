<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Http\Requests;

use App\Core\Traits\HandleApi;
use App\Modules\RiskManagement\Model\Enums\ViolationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class WarnDriverRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Admin role check should be in middleware
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(ViolationType::class)],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'complaint_id' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng nhập nội dung cảnh báo.',
            'reason.min' => 'Nội dung cảnh báo phải ít nhất 10 ký tự.',
        ];
    }
}
