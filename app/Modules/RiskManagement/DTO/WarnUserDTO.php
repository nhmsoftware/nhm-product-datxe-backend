<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\DTO;

use App\Modules\RiskManagement\Http\Requests\WarnDriverRequest;
use App\Modules\RiskManagement\Model\Enums\ViolationType;

final class WarnUserDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $adminId,
        public readonly ViolationType $type,
        public readonly string $reason,
        public readonly ?string $complaintId = null,
    ) {}

    public static function fromRequest(WarnDriverRequest $request, string $userId): self
    {
        return new self(
            userId: $userId,
            adminId: (string) $request->user()->id,
            type: ViolationType::from($request->input('type')),
            reason: $request->input('reason'),
            complaintId: $request->input('complaint_id'),
        );
    }
}
