<?php

declare(strict_types=1);

namespace App\Modules\User\Services\Auth;

use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\Gender;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use Illuminate\Support\Facades\DB;

final class RegisterService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Đăng ký tài khoản mới + tạo profile tương ứng.
     *
     * @return array{user: User, token: string}
     * @throws \App\Modules\User\Exceptions\UserAlreadyExistsException
     */
    public function handle(RegisterData $data): array
    {
        if ($this->userRepo->existsByPhone($data->phone)) {
            throw new \App\Modules\User\Exceptions\UserAlreadyExistsException($data->phone);
        }

        $user = DB::transaction(function () use ($data): User {

            $user = $this->userRepo->create([
                'phone'    => $data->phone,
                'password' => bcrypt($data->password),
                'role'     => $data->role->value,
            ]);

            match ($data->role) {
                UserRole::Customer => $this->userRepo->createCustomerProfile($user->id, [
                    'full_name' => $data->fullName,
                    'gender'    => Gender::Male,
                ]),
                default => null,
            };

            if ($data->deviceId) {
                $this->userRepo->upsertDevice($user->id, [
                    'device_id'   => $data->deviceId,
                    'token'       => $data->deviceToken,
                    'device_type' => $data->deviceType,
                ]);
            }

            return $user;
        });

        event(new \App\Modules\User\Events\UserRegistered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user->load('customerProfile'),
            'token' => $token,
        ];
    }
}
