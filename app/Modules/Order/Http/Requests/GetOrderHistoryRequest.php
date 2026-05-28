<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GetOrderHistoryRequest',
    properties: [
        new OA\Property(property: 'service_type', type: 'string', enum: ['ride', 'food', 'delivery', 'intercity', 'airport', 'chauffeur'], nullable: true),
        new OA\Property(property: 'status', type: 'string', nullable: true),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
    ]
)]
final class GetOrderHistoryRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type' => ['nullable', 'string', 'in:ride,food,delivery,intercity,airport,chauffeur'],
            'status' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
