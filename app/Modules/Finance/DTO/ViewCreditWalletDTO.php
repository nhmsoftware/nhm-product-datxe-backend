<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

final class ViewCreditWalletDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly int $page = 1,
        public readonly int $limit = 10,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) $request->user()->id,
            page: (int) $request->query('page', 1),
            limit: (int) $request->query('limit', 10),
        );
    }
}
