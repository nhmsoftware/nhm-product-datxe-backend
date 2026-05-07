<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use Illuminate\Http\Request;

final class GetMenuDTO
{
    public function __construct(
        public readonly string  $merchantProfileId,
        public readonly ?string $categoryId = null,
        public readonly ?string $search = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $merchantProfileId = (string) $request->user()->merchantProfile?->id;

        return new self(
            merchantProfileId: $merchantProfileId,
            categoryId: $request->query('category_id'),
            search: $request->query('search'),
        );
    }
}
