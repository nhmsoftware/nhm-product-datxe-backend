<?php

declare(strict_types=1);

namespace App\Modules\Order\DTO;

use Illuminate\Http\Request;

final class GetOrderHistoryFilterDTO
{
    public function __construct(
        public readonly string $customerId,
        public readonly ?string $serviceType = null,
        public readonly ?string $status = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly int $perPage = 15,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            customerId: (string) $request->user()->id,
            serviceType: $request->input('service_type'),
            status: $request->input('status') !== null ? (string)$request->input('status') : null,
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
            perPage: (int) $request->input('per_page', 15),
        );
    }
}
