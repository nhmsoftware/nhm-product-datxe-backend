<?php

declare(strict_types=1);

namespace App\Modules\User\DTO;

use App\Modules\User\Http\Requests\EditProfileRequest;
use Illuminate\Http\UploadedFile;

/**
 * DTO cho request cập nhật profile (UC-05).
 * Chứa ID người dùng và mảng dữ liệu đã được validate (để hỗ trợ partial update chính xác).
 */
final class UpdateProfileDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly array $data,
        public readonly ?UploadedFile $avatar = null,
    ) {
    }

    public static function fromRequest(EditProfileRequest $request): self
    {
        return new self(
            userId: (string) $request->user()->id,
            data:   $request->safe()->except(['avatar']),
            avatar: $request->file('avatar'),
        );
    }
}
