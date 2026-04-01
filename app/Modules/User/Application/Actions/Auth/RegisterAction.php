<?php

declare(strict_types=1);

namespace Modules\User\Application\Actions\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\User\Application\DTOs\Auth\RegisterDTO;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Enums\Gender;
use Modules\User\Domain\Enums\UserRole;
use Modules\User\Domain\Events\UserRegistered;
use Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use Modules\User\Domain\Interfaces\UserRepositoryInterface;
use Modules\User\Domain\ValueObjects\Password;
use Modules\User\Domain\ValueObjects\Phone;

final class RegisterAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Đăng ký tài khoản mới + tạo profile tương ứng.
     * Trả về User mới + Sanctum token.
     *
     * @return array{user: User, token: string}
     * @throws UserAlreadyExistsException
     */
    public function execute(RegisterDTO $dto): array
    {
        // ── 1. Validate Value Objects ──────────────────────────
        $phone    = new Phone($dto->phone);
        $password = new Password($dto->password);

        // ── 2. Kiểm tra trùng phone ────────────────────────────
        if ($this->userRepo->existsByPhone((string) $phone)) {
            throw new UserAlreadyExistsException((string) $phone);
        }

        // ── 3. Persist trong transaction ───────────────────────
        $user = DB::transaction(function () use ($dto, $phone, $password): User {

            $user = $this->userRepo->create([
                'name'     => $dto->fullName,
                'phone'    => (string) $phone,
                'password' => $password->hash(),
                'role'     => $dto->role->value,
            ]);

            // Tạo profile theo role
            match ($dto->role) {
                UserRole::Customer => $this->userRepo->createCustomerProfile($user->id, [
                    'full_name' => $dto->fullName,
                    'gender'    => Gender::Male, // default: Nam — FE có thể cập nhật sau
                ]),
                default => null, // Driver/Merchant cần KYC riêng
            };

            // Lưu thiết bị nếu có
            if ($dto->deviceId) {
                $this->userRepo->upsertDevice($user->id, [
                    'device_id'   => $dto->deviceId,
                    'token'       => $dto->deviceToken,
                    'device_type' => $dto->deviceType,
                ]);
            }

            return $user;
        });

        // ── 4. Bắn Event (Listener sẽ gửi welcome SMS, ...) ───
        event(new UserRegistered($user));

        // ── 5. Cấp token ───────────────────────────────────────
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user->load('customerProfile'),
            'token' => $token,
        ];
    }
}
