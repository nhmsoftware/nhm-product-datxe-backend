<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

final class ToggleFreeModeRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_free_mode' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_free_mode.required' => 'Không thể cập nhật chế độ miễn phí. Vui lòng thử lại.',
            'is_free_mode.boolean'  => 'Không thể cập nhật chế độ miễn phí. Vui lòng thử lại.',
        ];
    }
}
