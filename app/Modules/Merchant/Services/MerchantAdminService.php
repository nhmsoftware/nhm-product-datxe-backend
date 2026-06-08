<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Logs\Logging;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\CreateMerchantDTO;
use App\Modules\Merchant\DTO\MerchantFilterDTO;
use App\Modules\Merchant\DTO\UpdateMerchantDTO;
use App\Modules\Merchant\Interfaces\MerchantAdminServiceInterface;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\Merchant\Interfaces\MenuRepositoryInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Events\UserStatusUpdated;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Model\Enums\KycType;
use App\Modules\Driver\Model\Enums\KycStatus as AppKycStatus;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

final class MerchantAdminService extends BaseService implements MerchantAdminServiceInterface
{
    private const MERCHANT_ID_RETRY_TIMES = 3;
    private const STORAGE_PATH = 'merchant-admin';

    public function __construct(
        private readonly MerchantRepositoryInterface           $merchantRepository,
        private readonly UserRepositoryInterface               $userRepository,
        private readonly DriverRegistrationRepositoryInterface $driverRegistrationRepository,
        private readonly MenuRepositoryInterface               $menuRepository,
    ) {}

    public function getMerchants(MerchantFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();
            return $this->merchantRepository->searchMerchants($dto);
        });
    }

    public function getMerchantDetails(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $this->authorizeAdminOrFail();
            $merchant = $this->merchantRepository->findById($id);
            $this->validate($merchant !== null, 'Merchant không tồn tại.', 404);
            
            $merchant->load(['user', 'user.customerProfile']);
            
            // Lấy thông tin hồ sơ xét duyệt nếu có
            $application = $this->driverRegistrationRepository->findActiveApplicationByUser($merchant->user_id, KycType::MERCHANTS);

            // Lấy thực đơn (menu) của nhà hàng
            $menu = $this->menuRepository->getFullMenu($id);

            return [
                'merchant'    => $merchant,
                'application' => $application,
                'menu'        => $menu,
            ];
        });
    }

    public function createMerchant(CreateMerchantDTO $dto): ServiceReturn
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

            $this->validate(
                !$this->merchantRepository->isStoreNameExists($dto->storeName),
                'Tên cửa hàng đã tồn tại. Vui lòng nhập tên khác.',
                409
            );

            $plainPassword = $dto->password ?: $this->generateTemporaryPassword();
            $merchant = $this->persistMerchantWithRetry($dto, $plainPassword);

            Logging::userActivity(
                action: 'admin_create_merchant',
                description: "Tạo merchant #{$merchant->id}",
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success([
                'id' => $merchant->id,
                'store_name' => $merchant->store_name,
                'store_address' => $merchant->store_address,
                'status' => $merchant->status?->value,
                'status_label' => $merchant->status?->label(),
                'is_active' => $merchant->user?->is_active,
                'owner_name' => $merchant->user?->customerProfile?->full_name,
                'phone' => $merchant->user?->phone,
                'email' => $merchant->user?->email,
                'temporary_password' => $dto->password ? null : $plainPassword,
                'created_at' => $merchant->created_at?->toIso8601String(),
            ], 'Tạo Merchant thành công.');
        }, useTransaction: false);
    }

    public function updateMerchant(UpdateMerchantDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();

            $merchant = $this->merchantRepository->findById($dto->merchantId);
            $this->validate($merchant !== null, 'Không tìm thấy Merchant.', 404);

            $user = $merchant->user;
            $this->validate($user !== null, 'Không tìm thấy chủ sở hữu Merchant.', 404);

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

            $this->validate(
                !$this->merchantRepository->isStoreNameExists($dto->storeName, $merchant->user_id),
                'Tên cửa hàng đã tồn tại. Vui lòng nhập tên khác.',
                409
            );

            if ($dto->isActive === false) {
                $this->validate(
                    !$this->merchantRepository->hasActiveOrders($merchant->id),
                    'Không thể ngừng hoạt động Merchant đang có đơn hàng đang xử lý.',
                    409
                );
            }

            $user->update([
                'phone' => $dto->phone,
                'email' => $dto->email,
                'is_active' => $dto->isActive ?? $user->is_active,
                'lock_reason' => $dto->isActive === false ? $dto->lockReason : null,
                'locked_at' => $dto->isActive === false ? now() : null,
                'lock_expired_at' => null,
            ]);

            $user->customerProfile()?->update([
                'full_name' => $dto->ownerName,
            ]);

            $merchantData = [
                'store_name' => $dto->storeName,
                'store_address' => $dto->storeAddress,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
                'business_type' => $dto->businessType?->value,
                'business_license' => $dto->businessLicense,
            ];

            if ($dto->status !== null) {
                $merchantData['status'] = $dto->status->value;
                if ($dto->status !== KycStatus::Rejected) {
                    $merchantData['reject_reason'] = null;
                }
            }

            foreach ($dto->files as $field => $file) {
                if ($file) {
                    $merchantData[$field] = $this->storeMerchantFile($file, $merchant->id, $field);
                }
            }

            $merchant->update($merchantData);

            if ($dto->openingTime || $dto->closingTime) {
                $this->syncSimpleOpeningHours($merchant->id, $dto->openingTime, $dto->closingTime);
            }

            Logging::userActivity(
                action: 'admin_update_merchant',
                description: "Cập nhật merchant #{$merchant->id}",
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success([
                'id' => $merchant->id,
                'store_name' => $merchant->fresh()->store_name,
                'store_address' => $merchant->fresh()->store_address,
                'status' => $merchant->fresh()->status?->value,
                'status_label' => $merchant->fresh()->status?->label(),
                'is_active' => $merchant->fresh()->user?->is_active,
            ], 'Cập nhật Merchant thành công.');
        }, useTransaction: true);
    }

    public function deleteMerchant(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $this->authorizeAdminOrFail();

            $merchant = $this->merchantRepository->findById($id);
            $this->validate($merchant !== null, 'Không tìm thấy Merchant.', 404);

            $this->validate(
                !$this->merchantRepository->hasActiveOrders($merchant->id),
                'Không thể ngừng hoạt động Merchant đang có đơn hàng đang xử lý.',
                409
            );

            $user = $merchant->user;
            $this->validate($user !== null, 'Không tìm thấy chủ sở hữu Merchant.', 404);

            $merchant->delete();
            $user->delete();

            Logging::userActivity(
                action: 'admin_delete_merchant',
                description: "Xóa mềm merchant #{$merchant->id}",
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success([
                'id' => $id,
            ], 'Xóa Merchant thành công.');
        }, useTransaction: true);
    }

    public function approveMerchant(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $this->authorizeAdminOrFail();
            $merchant = $this->merchantRepository->findById($id);
            $this->validate($merchant !== null, 'Merchant không tồn tại.', 404);
            $this->validate($merchant->status !== KycStatus::Approved, 'Merchant đã được duyệt trước đó.', 400);
            $this->validate($merchant->status === KycStatus::Pending, 'Hồ sơ Merchant không ở trạng thái chờ duyệt.', 400);

            // 1. Cập nhật trạng thái Merchant Profile
            $this->merchantRepository->updateById($id, [
                'status' => KycStatus::Approved->value
            ]);

            // 2. Cập nhật trạng thái hồ sơ xét duyệt (nếu có)
            $application = $this->driverRegistrationRepository->findActiveApplicationByUser($merchant->user_id, KycType::MERCHANTS);
            if ($application) {
                $this->driverRegistrationRepository->updateStatus($application->id, AppKycStatus::APPROVED);
            }

            // 3. Nâng cấp User role
            $this->userRepository->updateRole($merchant->user_id, UserRole::Merchants);

            event(new \App\Modules\Merchant\Events\MerchantApproved($id, $merchant->user_id));

            return [
                'id'     => $id,
                'status' => KycStatus::Approved->label(),
            ];
        }, useTransaction: true);
    }

    public function rejectMerchant(string $id, string $reason): ServiceReturn
    {
        return $this->execute(function () use ($id, $reason) {
            $this->authorizeAdminOrFail();
            $merchant = $this->merchantRepository->findById($id);
            $this->validate($merchant !== null, 'Merchant không tồn tại.', 404);
            $this->validate($merchant->status === KycStatus::Pending, 'Hồ sơ Merchant đã được xử lý trước đó.', 400);
            $this->validate(!empty(trim($reason)), 'Vui lòng nhập lý do từ chối.', 400);

            // 1. Cập nhật trạng thái Merchant Profile & Lý do
            $this->merchantRepository->updateById($id, [
                'status'        => KycStatus::Rejected->value,
                'reject_reason' => $reason,
            ]);

            // 2. Cập nhật trạng thái hồ sơ xét duyệt (nếu có)
            $application = $this->driverRegistrationRepository->findActiveApplicationByUser($merchant->user_id, KycType::MERCHANTS);
            if ($application) {
                $this->driverRegistrationRepository->updateStatus($application->id, AppKycStatus::REJECTED, $reason);
            }

            event(new \App\Modules\Merchant\Events\MerchantRejected($id, $merchant->user_id, $reason));

            return [
                'id'     => $id,
                'status' => KycStatus::Rejected->label(),
            ];
        }, useTransaction: true);
    }

    public function toggleLockMerchant(string $id, bool $lock, ?string $reason = null, ?int $lockedDays = null): ServiceReturn
    {
        return $this->execute(function () use ($id, $lock, $reason, $lockedDays) {
            $this->authorizeAdminOrFail();
            $merchant = $this->merchantRepository->findById($id);
            $this->validate($merchant !== null, 'Merchant không tồn tại.', 404);

            $user = $merchant->user;
            $this->validate($user !== null, 'Không tìm thấy người dùng.', 404);

            // Kiểm tra trạng thái hiện tại (A5)
            $isCurrentlyLocked = !$user->is_active;
            if ($isCurrentlyLocked === $lock) {
                $this->throw('Trạng thái tài khoản đã được cập nhật trước đó.', 400);
            }

            if ($lock) {
                $this->validate(
                    !$this->merchantRepository->hasActiveOrders($merchant->id),
                    'Không thể ngừng hoạt động Merchant đang có đơn hàng đang xử lý.',
                    409
                );
            }

            if ($lock) {
                // Logic Khóa (Lock)
                $this->validate(!empty(trim((string)$reason)), 'Vui lòng nhập lý do khóa tài khoản.', 400);
                
                $days = $lockedDays ?? 2;
                $this->validate($days > 0, 'Số ngày khóa không hợp lệ.', 400);

                $lockedAt = now();
                $expiredAt = $lockedAt->copy()->addDays($days);

                $this->userRepository->updateById($user->id, [
                    'is_active'       => false,
                    'lock_reason'     => $reason,
                    'locked_days'     => $days,
                    'locked_at'       => $lockedAt,
                    'lock_expired_at' => $expiredAt,
                ]);
            } else {
                // Logic Mở khóa (Unlock)
                $this->userRepository->updateById($user->id, [
                    'is_active'       => true,
                    'lock_reason'     => null,
                    'locked_days'     => null,
                    'locked_at'       => null,
                    'lock_expired_at' => null,
                ]);
            }

            // Phát sự kiện realtime đồng bộ với Driver/Customer
            UserStatusUpdated::dispatch(
                $user->id,
                !$lock,
                $reason,
                $lock ? $expiredAt->toIso8601String() : null
            );

            event(new \App\Modules\Merchant\Events\MerchantAccountStatusChanged(
                $id, 
                $user->id, 
                $lock, 
                $reason,
                $lock ? ($expiredAt ?? null) : null
            ));

            return [
                'id'        => $id,
                'is_active' => !$lock,
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

    private function persistMerchantWithRetry(CreateMerchantDTO $dto, string $plainPassword)
    {
        for ($attempt = 1; $attempt <= self::MERCHANT_ID_RETRY_TIMES; $attempt++) {
            DB::beginTransaction();

            try {
                $user = $this->userRepository->create([
                    'phone' => $dto->phone,
                    'email' => $dto->email,
                    'password' => Hash::make($plainPassword),
                    'role' => UserRole::Merchants,
                    'is_verified' => true,
                    'is_phone_verified' => true,
                    'is_active' => $dto->isActive ?? true,
                ]);

                $this->userRepository->createCustomerProfile($user, [
                    'full_name' => $dto->ownerName,
                ]);

                $merchant = $this->merchantRepository->create([
                    'user_id' => $user->id,
                    'store_name' => $dto->storeName,
                    'store_address' => $dto->storeAddress,
                    'latitude' => $dto->latitude,
                    'longitude' => $dto->longitude,
                    'business_type' => $dto->businessType?->value,
                    'business_license' => $dto->businessLicense,
                    'status' => ($dto->status ?? KycStatus::Pending)->value,
                    'is_open' => (bool) ($dto->isActive ?? true),
                    'business_license_image' => $dto->files['business_license_image']
                        ? $this->storeMerchantFile($dto->files['business_license_image'], $user->id, 'business_license_image')
                        : null,
                    'store_image' => $dto->files['store_image']
                        ? $this->storeMerchantFile($dto->files['store_image'], $user->id, 'store_image')
                        : null,
                ]);

                if ($dto->openingTime || $dto->closingTime) {
                    $this->syncSimpleOpeningHours($merchant->id, $dto->openingTime, $dto->closingTime);
                }

                DB::commit();

                return $merchant->fresh(['user', 'user.customerProfile']);
            } catch (QueryException $e) {
                DB::rollBack();

                if ($this->isDuplicatePhoneException($e)) {
                    $this->throw('Số điện thoại này đã tồn tại trong hệ thống.', 409);
                }

                if ($this->isDuplicateEmailException($e)) {
                    $this->throw('Email này đã tồn tại trong hệ thống.', 409);
                }

                if ($this->isPrimaryKeyCollision($e) && $attempt < self::MERCHANT_ID_RETRY_TIMES) {
                    continue;
                }

                if ($this->isPrimaryKeyCollision($e)) {
                    $this->throw('Không thể tạo mã Merchant. Vui lòng thử lại.', 500);
                }

                throw $e;
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }

        $this->throw('Không thể tạo mã Merchant. Vui lòng thử lại.', 500);
    }

    private function syncSimpleOpeningHours(string $merchantProfileId, ?string $openingTime, ?string $closingTime): void
    {
        if (!$openingTime || !$closingTime) {
            return;
        }

        $schedule = collect(range(1, 7))
            ->map(fn ($day) => [
                'day_of_week' => $day,
                'opening_time' => $openingTime,
                'closing_time' => $closingTime,
                'is_closed' => false,
                'is_overnight' => false,
            ])
            ->all();

        $this->merchantRepository->updateOpeningHoursSchedule($merchantProfileId, $schedule);
    }

    private function storeMerchantFile(\Illuminate\Http\UploadedFile $file, string $merchantId, string $field): string
    {
        $folder = self::STORAGE_PATH . '/' . $merchantId;
        $name = $field . '-' . uniqid('', true) . '.' . $file->extension();
        $path = $file->storeAs($folder, $name, 'local');

        if ($path === false) {
            $this->throw('Tải file thất bại. Vui lòng thử lại.', 500);
        }

        return $path;
    }

    private function generateTemporaryPassword(): string
    {
        return sprintf('Tmp@%06d', random_int(0, 999999));
    }

    private function isPrimaryKeyCollision(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique constraint failed: users.id')
            || str_contains($message, 'unique constraint failed: merchant_profiles.id')
            || str_contains($message, 'users.primary')
            || str_contains($message, 'merchant_profiles.primary')
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
