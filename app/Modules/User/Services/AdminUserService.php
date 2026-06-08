<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Core\Logs\Logging;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\User\DTO\Admin\CreateCustomerDTO;
use App\Modules\User\DTO\Admin\ListUsersDTO;
use App\Modules\User\DTO\Admin\UpdateCustomerDTO;
use App\Modules\User\DTO\Admin\UpdateUserStatusDTO;
use App\Modules\User\Events\UserStatusUpdated;
use App\Modules\User\Interfaces\AdminUserServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AdminUserService extends BaseService implements AdminUserServiceInterface
{
    private const CUSTOMER_ID_RETRY_TIMES = 3;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @inheritDoc
     */
    public function listCustomers(ListUsersDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();

            $paginator = $this->userRepository->findCustomers($dto->toArray(), $dto->perPage);
            
            $paginator->getCollection()->transform(function ($user) {
                return [
                    'id'              => $user->id,
                    'full_name'       => $user->full_name,
                    'phone'           => $user->phone,
                    'email'           => $user->email,
                    'is_active'       => $user->is_active,
                    'lock_reason'     => $user->lock_reason,
                    'lock_expired_at' => $user->lock_expired_at?->toIso8601String(),
                    'locked_days'     => $user->locked_days,
                    'total_rides'    => $user->customerProfile?->total_rides ?? 0,
                    'created_at'     => $user->created_at?->toIso8601String(),
                ];
            });

            return $paginator;
        });
    }


    /**
     * @inheritDoc
     */
    public function getCustomerDetail(string|int $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $this->authorizeAdminOrFail();

            $user = $this->userRepository->findDetailById($userId);
            $this->validate($user !== null && $user->role === UserRole::Customer, 'Không tìm thấy khách hàng.', 404);
            
            return [
                'id'                => $user->id,
                'full_name'         => $user->full_name,
                'phone'             => $user->phone,
                'email'             => $user->email,
                'gender'            => $user->gender?->value,
                'gender_label'      => $user->gender?->label(),
                'birthday'          => $user->birthday?->format('Y-m-d'),
                'address'           => $user->address,
                'avatar'            => $user->avatar,
                'is_active'         => $user->is_active,
                'lock_reason'       => $user->lock_reason,
                'locked_at'         => $user->locked_at?->toIso8601String(),
                'lock_expired_at'   => $user->lock_expired_at?->toIso8601String(),
                'locked_days'       => $user->locked_days,
                'created_at'        => $user->created_at?->toIso8601String(),
                'role'              => $user->role->value,
                'role_label'        => $user->role->label(),
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function createCustomer(CreateCustomerDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();

            $this->validate(
                !$this->userRepository->existsByPhone($dto->phone),
                'Số điện thoại này đã tồn tại trong hệ thống.',
                409
            );

            if ($dto->email !== null) {
                $this->validate(
                    $this->userRepository->findByEmail($dto->email) === null,
                    'Email này đã tồn tại trong hệ thống.',
                    409
                );
            }

            $plainPassword = $dto->password ?: $this->generateTemporaryPassword();
            $user = $this->persistCustomerWithRetry($dto, $plainPassword)->load('customerProfile');

            return $this->success([
                'id' => $user->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'gender' => $user->gender?->value,
                'gender_label' => $user->gender?->label(),
                'birthday' => $user->birthday?->format('Y-m-d'),
                'address' => $user->address,
                'is_active' => $user->is_active,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'created_at' => $user->created_at?->toIso8601String(),
                'temporary_password' => $dto->password ? null : $plainPassword,
            ], 'Tạo khách hàng thành công.');
        }, useTransaction: false);
    }

    /**
     * @inheritDoc
     */
    public function updateCustomer(UpdateCustomerDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();

            $user = $this->userRepository->findDetailById($dto->userId);
            $this->validate($user !== null && $user->role === UserRole::Customer, 'Không tìm thấy khách hàng.', 404);

            $existingPhoneUser = $this->userRepository->findByPhone($dto->phone);
            if ($existingPhoneUser && (string) $existingPhoneUser->id !== (string) $user->id) {
                $this->throw('Số điện thoại này đã được sử dụng.', 409);
            }

            if ($dto->email !== null) {
                $existingEmailUser = $this->userRepository->findByEmail($dto->email);
                if ($existingEmailUser && (string) $existingEmailUser->id !== (string) $user->id) {
                    $this->throw('Email này đã được sử dụng.', 409);
                }
            }

            $userData = [
                'phone' => $dto->phone,
                'email' => $dto->email,
                'address' => $dto->address,
            ];

            if ($dto->isActive !== null && $dto->isActive !== $user->is_active) {
                $userData['is_active'] = $dto->isActive;

                if ($dto->isActive) {
                    $userData['lock_reason'] = null;
                    $userData['locked_days'] = null;
                    $userData['locked_at'] = null;
                    $userData['lock_expired_at'] = null;
                } else {
                    $userData['lock_reason'] = null;
                    $userData['locked_days'] = null;
                    $userData['locked_at'] = now();
                    $userData['lock_expired_at'] = null;
                }
            }

            $user->update($userData);

            $user->customerProfile()?->update([
                'full_name' => $dto->fullName,
                'gender' => $dto->gender?->value,
                'birthday' => $dto->birthday,
                'address' => $dto->address,
            ]);

            $updatedUser = $user->fresh()->load('customerProfile');

            Logging::userActivity(
                action: 'admin_update_customer',
                description: "Cập nhật khách hàng #{$updatedUser->id}",
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success([
                'id' => $updatedUser->id,
                'full_name' => $updatedUser->full_name,
                'phone' => $updatedUser->phone,
                'email' => $updatedUser->email,
                'gender' => $updatedUser->gender?->value,
                'gender_label' => $updatedUser->gender?->label(),
                'birthday' => $updatedUser->birthday?->format('Y-m-d'),
                'address' => $updatedUser->address,
                'is_active' => $updatedUser->is_active,
                'role' => $updatedUser->role->value,
                'role_label' => $updatedUser->role->label(),
                'updated_at' => $updatedUser->updated_at?->toIso8601String(),
            ], 'Cập nhật khách hàng thành công.');
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function deleteCustomer(string|int $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $this->authorizeAdminOrFail();

            $user = $this->userRepository->findDetailById($userId);
            $this->validate($user !== null && $user->role === UserRole::Customer, 'Không tìm thấy khách hàng.', 404);

            $this->validate(
                !$this->userRepository->hasActiveRide($userId) && !$this->userRepository->hasActiveFoodOrder($userId),
                'Không thể xóa khách hàng đang có đơn hoặc chuyến đang xử lý.',
                409
            );

            $this->userRepository->softDeleteCustomer($user);

            Logging::userActivity(
                action: 'admin_delete_customer',
                description: "Xóa mềm khách hàng #{$user->id}",
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success([
                'user_id' => (string) $user->id,
                'message' => 'Xóa khách hàng thành công.',
            ], 'Xóa khách hàng thành công.');
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     * UC-78 Lock/Unlock User
     */
    public function updateUserStatus(UpdateUserStatusDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();

            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null && $user->role === UserRole::Customer, 'Không tìm thấy khách hàng.', 404);

            $updateData = ['is_active' => $dto->isActive];

            if (!$dto->isActive) {
                // Thao tác Lock User
                $this->validate($user->is_active, 'Trạng thái tài khoản đã được cập nhật trước đó.');
                
                $lockedAt = now();
                $lockExpiredAt = null;

                if ($dto->lockExpiredAt) {
                    $lockExpiredAt = \Illuminate\Support\Carbon::parse($dto->lockExpiredAt);
                } else {
                    $lockedDays = $dto->lockedDays ?? 2; // Default to 2 days as per UC-84
                    $lockExpiredAt = (clone $lockedAt)->addDays($lockedDays);
                }

                $updateData = array_merge($updateData, [
                    'lock_reason'     => $dto->reason,
                    'locked_days'     => $dto->lockedDays ?? ($dto->lockExpiredAt ? $lockedAt->diffInDays($lockExpiredAt) : 2),
                    'locked_at'       => $lockedAt,
                    'lock_expired_at' => $lockExpiredAt,
                ]);
            } else {
                // Thao tác Unlock User
                $this->validate(!$user->is_active, 'Trạng thái tài khoản đã được cập nhật trước đó.');

                $updateData = array_merge($updateData, [
                    'lock_reason'     => null,
                    'locked_days'     => null,
                    'locked_at'       => null,
                    'lock_expired_at' => null,
                ]);
            }

            $success = $this->userRepository->updateActiveStatus($dto->userId, $updateData);
            $this->validate($success, 'Không thể cập nhật trạng thái tài khoản. Vui lòng thử lại.');

            // Phát sự kiện realtime
            UserStatusUpdated::dispatch(
                $dto->userId,
                $dto->isActive,
                $dto->reason,
                isset($lockExpiredAt) ? $lockExpiredAt->toIso8601String() : null
            );

            Logging::userActivity(
                action: 'admin_update_customer_status',
                description: "Cập nhật trạng thái khách hàng #{$dto->userId} thành " . ($dto->isActive ? 'active' : 'locked'),
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return [
                'user_id'   => $dto->userId,
                'is_active' => $dto->isActive,
                'message'   => $dto->isActive ? 'Mở khóa tài khoản thành công.' : 'Khóa tài khoản thành công.',
            ];
        }, useTransaction: true);
    }

    private function authorizeAdminOrFail(): void
    {
        $requestUser = request()->user();

        $this->validate(
            $requestUser !== null && method_exists($requestUser, 'isAdmin') && $requestUser->isAdmin(),
            'Bạn không có quyền thực hiện thao tác này.',
            403
        );
    }

    private function persistCustomerWithRetry(CreateCustomerDTO $dto, string $plainPassword)
    {
        for ($attempt = 1; $attempt <= self::CUSTOMER_ID_RETRY_TIMES; $attempt++) {
            DB::beginTransaction();

            try {
                $user = $this->userRepository->create([
                    'phone' => $dto->phone,
                    'email' => $dto->email,
                    'password' => Hash::make($plainPassword),
                    'role' => UserRole::Customer,
                    'is_verified' => true,
                    'is_phone_verified' => true,
                    'is_active' => true,
                ]);

                $this->userRepository->createCustomerProfile($user, [
                    'full_name' => $dto->fullName,
                    'gender' => $dto->gender?->value,
                    'birthday' => $dto->birthday,
                    'address' => $dto->address,
                ]);

                DB::commit();

                return $user->refresh();
            } catch (QueryException $e) {
                DB::rollBack();

                if ($this->isDuplicatePhoneException($e)) {
                    $this->throw('Số điện thoại này đã tồn tại trong hệ thống.', 409);
                }

                if ($this->isDuplicateEmailException($e)) {
                    $this->throw('Email này đã tồn tại trong hệ thống.', 409);
                }

                if ($this->isPrimaryKeyCollision($e) && $attempt < self::CUSTOMER_ID_RETRY_TIMES) {
                    continue;
                }

                if ($this->isPrimaryKeyCollision($e)) {
                    $this->throw('Không thể tạo mã khách hàng. Vui lòng thử lại.', 500);
                }

                throw $e;
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }

        $this->throw('Không thể tạo mã khách hàng. Vui lòng thử lại.', 500);
    }

    private function generateTemporaryPassword(): string
    {
        return sprintf('Tmp@%06d', random_int(0, 999999));
    }

    private function isPrimaryKeyCollision(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique constraint failed: users.id')
            || str_contains($message, 'unique constraint failed: customer_profiles.id')
            || str_contains($message, 'users.primary')
            || str_contains($message, 'customer_profiles.primary')
            || (str_contains($message, 'duplicate entry') && str_contains($message, 'primary'));
    }

    private function isDuplicatePhoneException(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique constraint failed: users.phone')
            || str_contains($message, 'users.phone')
            || str_contains($message, 'users_phone_unique');
    }

    private function isDuplicateEmailException(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique constraint failed: users.email')
            || str_contains($message, 'users.email')
            || str_contains($message, 'users_email_unique');
    }
}
