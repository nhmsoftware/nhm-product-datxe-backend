<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use App\Modules\Auth\Http\Requests\AppleLoginRequest;

/**
 * DTO cho request đăng nhập bằng Apple.
 */
final class AppleLoginDTO
{
    public function __construct(
        public readonly string  $idToken,
        public readonly ?array  $name        = null,
        public readonly ?string $deviceId    = null,
        public readonly ?string $deviceToken = null,
        public readonly ?string $deviceType  = null,
    ) {
    }

    public static function fromRequest(AppleLoginRequest $request): self
    {
        // Apple gửi thông tin user dạng JSON string, cần decode nếu có
        $nameRaw  = $request->input('user');
        $nameData = $nameRaw ? json_decode($nameRaw, true) : null;

        return new self(
            idToken:     $request->string('id_token')->toString(),
            name:        is_array($nameData) ? ($nameData['name'] ?? null) : null,
            deviceId:    $request->input('device_id'),
            deviceToken: $request->input('device_token'),
            deviceType:  $request->input('device_type'),
        );
    }
}
