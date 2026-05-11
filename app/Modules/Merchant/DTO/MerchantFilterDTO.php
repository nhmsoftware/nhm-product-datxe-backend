<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use Illuminate\Http\Request;

final class MerchantFilterDTO
{
    public function __construct(
        public readonly ?string $keyword = null,
        public readonly ?string $storeName = null,
        public readonly ?string $ownerName = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?bool $isActive = null,
        public readonly ?int $status = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            keyword: $request->filled('keyword') ? $request->string('keyword')->trim()->toString() : null,
            storeName: $request->string('store_name')->trim()->isEmpty() ? null : $request->string('store_name')->trim()->toString(),
            ownerName: $request->string('owner_name')->trim()->isEmpty() ? null : $request->string('owner_name')->trim()->toString(),
            phone: $request->string('phone')->trim()->isEmpty() ? null : $request->string('phone')->trim()->toString(),
            email: $request->string('email')->trim()->isEmpty() ? null : $request->string('email')->trim()->toString(),
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            status: $request->filled('status') ? (int) $request->input('status') : null,
            page: (int) $request->input('page', 1),
            limit: (int) $request->input('limit', 20),
        );
    }
}
