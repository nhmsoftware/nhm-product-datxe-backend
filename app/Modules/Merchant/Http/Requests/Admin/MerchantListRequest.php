<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Requests\Admin;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

final class MerchantListRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Admin check in middleware
    }

    public function rules(): array
    {
        return [
            'store_name' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'email'      => ['nullable', 'string', 'email', 'max:255'],
            'is_active'  => ['nullable', 'boolean'],
            'status'     => ['nullable', 'integer'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'limit'      => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
