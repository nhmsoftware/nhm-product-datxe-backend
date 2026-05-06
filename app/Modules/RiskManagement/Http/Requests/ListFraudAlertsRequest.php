<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation cho danh sách cảnh báo gian lận.
 */
final class ListFraudAlertsRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Quyền hạn sẽ được kiểm tra ở Middleware/Policy
    }

    public function rules(): array
    {
        return [
            'keyword'     => 'nullable|string|max:255',
            'target_type' => 'nullable|integer',
            'risk_level'  => 'nullable|integer',
            'status'      => 'nullable|integer',
            'fraud_type'  => 'nullable|integer',
            'per_page'    => 'nullable|integer|min:1|max:100',
            'page'        => 'nullable|integer|min:1',
        ];
    }
}
