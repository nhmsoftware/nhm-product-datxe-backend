<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Core\Traits\HandleApi;

final class DashboardReportRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Phân quyền qua middleware
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'interval'   => ['nullable', 'string', 'in:day,month,year'],
            'area'       => ['nullable', 'string'],
        ];
    }
}
