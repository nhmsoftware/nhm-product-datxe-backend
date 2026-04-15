<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use App\Modules\Auth\Http\Requests\GoogleLoginRequest;

/**
 * DTO cho request đăng nhập bằng Google.
 */
final class GoogleLoginDTO
{
    public function __construct(
        public readonly string  $idToken,
        public readonly ?string $deviceId    = null,
        public readonly ?string $deviceToken = null,
        public readonly ?string $deviceType  = null,
    ) {
    }

    public static function fromRequest(GoogleLoginRequest $request): self
    {
        return new self(
            idToken:     $request->string('id_token')->toString(),
            deviceId:    $request->input('device_id'),
            deviceToken: $request->input('device_token'),
            deviceType:  $request->input('device_type'),
        );
    }
}
