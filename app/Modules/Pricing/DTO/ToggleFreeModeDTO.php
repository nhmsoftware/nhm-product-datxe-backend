<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

use Illuminate\Http\Request;

final class ToggleFreeModeDTO
{
    public function __construct(
        public readonly bool $isFreeMode,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            isFreeMode: $request->boolean('is_free_mode'),
        );
    }
}
