<?php

declare(strict_types=1);

namespace App\Modules\User\Services\Auth;

use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use Illuminate\Support\Facades\Hash;

final class LoginService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Đăng nhập bằng phone + password.
     *
     * @return array{user: User, token: string}
     * @throws \App\Modules\User\Exceptions\AuthenticationFailedException
     */
    public function handle(LoginData $data): array
    {
        $user = $this->userRepo->findByPhone($data->phone);

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new \App\Modules\User\Exceptions\AuthenticationFailedException();
        }

        if ($data->deviceId) {
            $this->userRepo->upsertDevice($user->id, [
                'device_id'   => $data->deviceId,
                'token'       => $data->deviceToken,
                'device_type' => $data->deviceType,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

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
