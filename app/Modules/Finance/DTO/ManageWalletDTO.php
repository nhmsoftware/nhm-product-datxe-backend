<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

final class ManageWalletDTO
{
    public function __construct(
        public readonly int $userId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (int) $request->user()->id
        );
    }
}
