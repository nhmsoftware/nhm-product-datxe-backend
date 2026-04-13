<?php

declare(strict_types=1);

namespace App\Modules\User\DTO;

use App\Modules\User\Http\Requests\EditProfileRequest;

/**
 * DTO cho request cập nhật profile (UC-05).
 * Chứa ID người dùng và mảng dữ liệu đã được validate (để hỗ trợ partial update chính xác).
 */
final class UpdateProfileDTO
{
    public function __construct(
        public readonly int   $userId,
        public readonly array $data,
    ) {
    }

    public static function fromRequest(EditProfileRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            data:   $request->validated(),
        );
    }
}
