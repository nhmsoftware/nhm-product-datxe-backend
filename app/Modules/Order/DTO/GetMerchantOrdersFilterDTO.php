<?php

declare(strict_types=1);

namespace App\Modules\Order\DTO;

use Illuminate\Http\Request;

final class GetMerchantOrdersFilterDTO
{
    public function __construct(
        public readonly string $merchantId,
        public readonly ?string $statusGroup = null,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
    ) {}

    public static function fromRequest(Request $request, string $merchantId): self
    {
        return new self(
            merchantId: $merchantId,
            statusGroup: $request->input('status_group'),
            perPage: (int) $request->input('per_page', 20),
            page: (int) $request->input('page', 1),
        );
    }
}
