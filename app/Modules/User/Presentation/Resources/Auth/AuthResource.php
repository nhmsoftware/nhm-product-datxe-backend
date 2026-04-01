<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource dùng sau khi đăng ký / đăng nhập thành công.
 * Shape:
 * {
 *   "token": "...",
 *   "token_type": "Bearer",
 *   "user": { ... }
 * }
 */
class AuthResource extends JsonResource
{
    private string $token;

    public function withToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'token'      => $this->token,
            'token_type' => 'Bearer',
            'user'       => new UserResource($this->resource),
        ];
    }
}
