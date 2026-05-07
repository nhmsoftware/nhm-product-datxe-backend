<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use Illuminate\Http\Request;

final class DeleteMenuItemDTO
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $merchantProfileId,
    ) {}

    public static function fromRequest(Request $request, string $itemId): self
    {
        return new self(
            itemId: $itemId,
            merchantProfileId: (string) $request->user()->merchantProfile?->id,
        );
    }
}
