<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Http\Requests\AdminAssignVoucherRequest;

final class AssignVoucherDTO
{
    public function __construct(
        public readonly string $voucherId,
        public readonly array $userIds,
    ) {
    }

    public static function fromRequest(AdminAssignVoucherRequest $request): self
    {
        return new self(
            voucherId: $request->string('voucher_id')->toString(),
            userIds: $request->input('user_ids', []),
        );
    }
}
