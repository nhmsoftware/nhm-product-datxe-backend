<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

use Illuminate\Http\Request;

final class UpdateUserStatusDTO
{
    public function __construct(
        public readonly string|int $userId,
        public readonly bool       $isActive,
        public readonly ?string    $reason = null,
        public readonly ?int       $lockedDays = null,
    ) {}

    public static function fromRequest(Request $request, string|int $userId): self
    {
        return new self(
            userId:     $userId,
            isActive:   $request->boolean('is_active'),
            reason:     $request->input('reason'),
            lockedDays: $request->input('locked_days') ? (int) $request->input('locked_days') : null,
        );
    }
}
