<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ComplaintHandled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $complaintId,
        public readonly string $adminId,
        public readonly string $action,
        public readonly ?string $note = null,
        public readonly string $processedAt,
    ) {}
}
