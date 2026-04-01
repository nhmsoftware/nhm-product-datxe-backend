<?php

declare(strict_types=1);

namespace Modules\User\Application\Actions\Auth;

use Illuminate\Support\Facades\Hash;
use Modules\User\Application\DTOs\Auth\LoginDTO;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Exceptions\AuthenticationFailedException;
use Modules\User\Domain\Interfaces\UserRepositoryInterface;
use Modules\User\Domain\ValueObjects\Phone;

final class LoginAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Đăng nhập bằng phone + password.
     *
     * @return array{user: User, token: string}
     * @throws AuthenticationFailedException
     */
    public function execute(LoginDTO $dto): array
    {
        // ── 1. Normalize phone ──────────────────────────────────
        $phone = new Phone($dto->phone);

        // ── 2. Tìm user ─────────────────────────────────────────
        $user = $this->userRepo->findByPhone((string) $phone);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new AuthenticationFailedException();
        }

        // ── 3. Lưu thiết bị nếu có ─────────────────────────────
        if ($dto->deviceId) {
            $this->userRepo->upsertDevice($user->id, [
                'device_id'   => $dto->deviceId,
                'token'       => $dto->deviceToken,
                'device_type' => $dto->deviceType,
            ]);
        }

        // ── 4. Thu hồi token cũ rồi cấp token mới ─────────────
        // (1 device = 1 token; bỏ comment nếu muốn revoke all)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // ── 5. Load đúng profile theo role ─────────────────────
        $user->load($this->profileRelation($user));

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    private function profileRelation(User $user): string
    {
        return match (true) {
            $user->isCustomer() => 'customerProfile',
            $user->isDriver()   => 'driverProfile',
            default             => 'customerProfile',
        };
    }
}
