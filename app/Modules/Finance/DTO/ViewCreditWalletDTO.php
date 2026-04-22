<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

final class ViewCreditWalletDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            page: (int) $request->query('page', 1),
            limit: (int) $request->query('limit', 20),
        );
    }
}
