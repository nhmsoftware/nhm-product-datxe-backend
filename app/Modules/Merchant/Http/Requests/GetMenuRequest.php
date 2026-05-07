<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // For UC-57, we might not have mandatory inputs yet, 
            // but we can prepare for pagination or filtering by category.
            'category_id' => ['nullable', 'string'],
            'search'      => ['nullable', 'string', 'max:255'],
        ];
    }
}
