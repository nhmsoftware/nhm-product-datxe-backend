<?php

declare(strict_types=1);

namespace App\Modules\Complaint\DTO;

use App\Modules\Complaint\Http\Requests\HandleComplaintRequest;
use App\Modules\Complaint\Model\Enums\ComplaintResolutionAction;

final class HandleComplaintDTO
{
    public function __construct(
        public readonly string $complaintId,
        public readonly string $adminId,
        public readonly ComplaintResolutionAction $action,
        public readonly ?string $note = null,
    ) {}

    public static function fromRequest(HandleComplaintRequest $request, string $id): self
    {
        return new self(
            complaintId: $id,
            adminId: (string) $request->user()->id,
            action: ComplaintResolutionAction::from($request->input('action')),
            note: $request->input('note'),
        );
    }
}
