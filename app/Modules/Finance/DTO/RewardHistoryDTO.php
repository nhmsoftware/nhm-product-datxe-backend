<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Http\Requests\ViewRewardHistoryRequest;
use App\Modules\Finance\Model\Enums\RewardTransactionType;

final class RewardHistoryDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly ?RewardTransactionType $type,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly int $perPage,
    ) {}

    /**
     * Tạo DTO từ yêu cầu (UC-24)
     */
       public static function fromRequest(ViewRewardHistoryRequest $request): self
    {
        $typeVal = $request->input('type');
        $type = $typeVal ? RewardTransactionType::from((int) $typeVal) : null;

        return new self(
            customerId: (int) $request->user()->id,
            type: $type,
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
            perPage: (int) $request->input('per_page', 15),
        );
    }
}
