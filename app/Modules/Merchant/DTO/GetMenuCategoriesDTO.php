<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use Illuminate\Http\Request;

final class GetMenuCategoriesDTO
{
    public function __construct(
        public readonly string $merchantProfileId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            merchantProfileId: (string) $request->user()->merchantProfile?->id,
        );
    }
}
