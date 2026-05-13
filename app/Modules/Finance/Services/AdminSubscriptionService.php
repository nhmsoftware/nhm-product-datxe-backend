<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\CreateSubscriptionPackageDTO;
use App\Modules\Finance\DTO\UpdateSubscriptionPackageDTO;
use App\Modules\Finance\Interfaces\AdminSubscriptionServiceInterface;
use App\Modules\Finance\Interfaces\SubscriptionPackageRepositoryInterface;

final class AdminSubscriptionService extends BaseService implements AdminSubscriptionServiceInterface
{
    public function __construct(
        private readonly SubscriptionPackageRepositoryInterface $packageRepository,
    ) {}

    /**
     * UC-118: Lấy danh sách tất cả gói (kể cả đã vô hiệu)
     */
    public function listPackages(): ServiceReturn
    {
        return $this->execute(function (): array {
            $packages = $this->packageRepository->getAllPackages();

            return $packages->map(fn($pkg) => $this->formatPackage($pkg))->toArray();
        });
    }

    /**
     * UC-118: Tạo gói thuê bao mới
     * A2: price <= 0 → FormRequest đã chặn (gt:0)
     * A3: duration_days <= 0 → FormRequest đã chặn (gt:0)
     * A4: Tên gói đã tồn tại → kiểm tra thêm trong service
     */
    public function createPackage(CreateSubscriptionPackageDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // A4: Kiểm tra trùng tên
            $existing = $this->packageRepository->findByName($dto->name);
            $this->validate($existing === null, 'Gói thuê bao này đã tồn tại.', 422);

            $package = $this->packageRepository->create([
                'name'                          => $dto->name,
                'package_type'                  => $dto->packageType,
                'price'                         => $dto->price,
                'duration_days'                 => $dto->durationDays,
                'service_fee_reduction_percent' => $dto->serviceFeeReductionPercent,
                'description'                   => $dto->description,
                'is_active'                     => true,
            ]);

            return $this->formatPackage($package);
        }, useTransaction: true);
    }

    /**
     * UC-118: Cập nhật thông tin gói thuê bao
     */
    public function updatePackage(string $packageId, UpdateSubscriptionPackageDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($packageId, $dto): array {
            $package = $this->packageRepository->find($packageId);
            $this->validate($package !== null, 'Không tìm thấy gói thuê bao.', 404);

            // A4: Kiểm tra trùng tên (bỏ qua chính nó)
            $existing = $this->packageRepository->findByName($dto->name, $packageId);
            $this->validate($existing === null, 'Gói thuê bao này đã tồn tại.', 422);

            $updated = $this->packageRepository->updateById($packageId, [
                'name'                          => $dto->name,
                'package_type'                  => $dto->packageType,
                'price'                         => $dto->price,
                'duration_days'                 => $dto->durationDays,
                'service_fee_reduction_percent' => $dto->serviceFeeReductionPercent,
                'description'                   => $dto->description,
            ]);

            $this->validate($updated !== false, 'Không thể cập nhật Subscription Package.', 500);

            return $this->formatPackage($this->packageRepository->find($packageId));
        }, useTransaction: true);
    }

    /**
     * UC-118: Vô hiệu hóa gói thuê bao (A5)
     * - Chỉ ngừng bán cho tài xế mới (is_active = false)
     * - Gói tài xế đã mua vẫn còn hiệu lực đến ngày hết hạn
     */
    public function disablePackage(string $packageId): ServiceReturn
    {
        return $this->execute(function () use ($packageId): array {
            $package = $this->packageRepository->find($packageId);
            $this->validate($package !== null, 'Không tìm thấy gói thuê bao.', 404);
            $this->validate($package->is_active, 'Gói thuê bao này đã bị vô hiệu hóa.', 409);

            // A5: Kiểm tra xem có tài xế đang dùng không (chỉ để thông báo, không chặn)
            $hasActive = $this->packageRepository->hasActiveDriverSubscriptions($packageId);

            $this->packageRepository->updateById($packageId, ['is_active' => false]);

            return [
                'package_id'             => $packageId,
                'has_active_subscribers' => $hasActive,
                'message'                => $hasActive
                    ? 'Gói đã vô hiệu hóa. Các tài xế đang sử dụng vẫn có hiệu lực đến ngày hết hạn.'
                    : 'Gói thuê bao đã được vô hiệu hóa thành công.',
            ];
        }, useTransaction: true);
    }

    /**
     * Format package data cho response
     */
    private function formatPackage(mixed $pkg): array
    {
        return [
            'id'                            => (string) $pkg->id,
            'name'                          => $pkg->name,
            'package_type'                  => $pkg->package_type ?? 'monthly',
            'description'                   => $pkg->description,
            'price'                         => (float) $pkg->price,
            'duration_days'                 => (int) $pkg->duration_days,
            'service_fee_reduction_percent' => (float) $pkg->service_fee_reduction_percent,
            'is_active'                     => (bool) $pkg->is_active,
            'created_at'                    => $pkg->created_at?->toIso8601String(),
            'updated_at'                    => $pkg->updated_at?->toIso8601String(),
        ];
    }
}
