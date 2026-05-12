<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GetOrderStatsRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', Rule::in(['today', 'week', 'month'])],
        ];
    }
}
