<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

use Illuminate\Http\Request;

final class ListUsersDTO
{
    public function __construct(
        public readonly ?string $keyword,
        public readonly ?bool   $isActive,
        public readonly int     $perPage = 20,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            keyword:  $request->query('keyword'),
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            perPage:  (int) $request->query('per_page', 20),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'keyword'   => $this->keyword,
            'is_active' => $this->isActive,
        ], fn($value) => !is_null($value));
    }
}
