<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\AdminVoucherDTO;
use App\Modules\Finance\DTO\AssignVoucherDTO;
use App\Modules\Finance\DTO\CreateVoucherDTO;
use App\Modules\Finance\DTO\UpdateVoucherDTO;
use App\Modules\Finance\Interfaces\AdminVoucherServiceInterface;
use App\Modules\Finance\Interfaces\VoucherRepositoryInterface;
use App\Modules\Finance\Interfaces\VoucherWalletRepositoryInterface;
use App\Modules\Finance\Model\Voucher;

final class AdminVoucherService extends BaseService implements AdminVoucherServiceInterface
{
    public function __construct(
        private readonly VoucherRepositoryInterface $voucherRepository,
        private readonly VoucherWalletRepositoryInterface $voucherWalletRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function listVouchers(array $filters): ServiceReturn
    {
        return $this->execute(function () use ($filters): array {
            $paginator = $this->voucherRepository->search($filters);
            
            return [
                'items' => collect($paginator->items())->map(fn(Voucher $v) => AdminVoucherDTO::fromModel($v)->toArray())->toArray(),
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ]
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function getVoucherDetail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id): array {
            /** @var Voucher|null $voucher */
            $voucher = $this->voucherRepository->findById($id);
            $this->validate($voucher !== null, 'Không tìm thấy voucher.', 404);

            return AdminVoucherDTO::fromModel($voucher)->toArray();
        });
    }

    /**
     * @inheritDoc
     */
    public function createVoucher(CreateVoucherDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $voucher = $this->voucherRepository->create($dto->toArray());
            
            event(new \App\Modules\Finance\Events\VoucherCreated((string) $voucher->id)); 
            
            return AdminVoucherDTO::fromModel($voucher)->toArray();
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function updateVoucher(string $id, UpdateVoucherDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($id, $dto): array {
            /** @var Voucher|null $voucher */
            $voucher = $this->voucherRepository->findById($id);
            $this->validate($voucher !== null, 'Không tìm thấy voucher.', 404);

            // A4 - Voucher đã được sử dụng
            if ($voucher->used_count > 0) {
                $data = $dto->toArray();
                if (isset($data['discount_type']) || isset($data['discount_value'])) {
                    $this->throw('Voucher đã được sử dụng, không thể thay đổi loại giảm giá và giá trị giảm giá.');
                }
            }

            $updated = $this->voucherRepository->updateById($id, $dto->toArray());
            $this->validate($updated !== false, 'Cập nhật voucher thất bại.');

            event(new \App\Modules\Finance\Events\VoucherUpdated((string) $id));

            /** @var Voucher $voucher */
            $voucher = $this->voucherRepository->findById($id);
            return AdminVoucherDTO::fromModel($voucher)->toArray();
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function deleteVoucher(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id): bool {
            /** @var Voucher|null $voucher */
            $voucher = $this->voucherRepository->findById($id);
            $this->validate($voucher !== null, 'Không tìm thấy voucher.', 404);

            $success = $this->voucherRepository->deleteById($id);
            $this->validate($success, 'Xóa voucher thất bại.');

            return true;
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function assignVoucher(AssignVoucherDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): string {
            /** @var Voucher|null $voucher */
            $voucher = $this->voucherRepository->findById($dto->voucherId);
            
            // A1 - Voucher không tồn tại
            $this->validate($voucher !== null, 'Voucher không tồn tại.', 404);

            // A3 - Voucher đã hết hạn
            $this->validate(!$voucher->isExpired(), 'Voucher đã hết hạn và không thể gán cho người dùng.');
            $this->validate($voucher->is_active === true, 'Voucher không hoạt động.');

            $count = 0;
            $alreadyAssignedCount = 0;

            foreach ($dto->userIds as $userId) {
                // A4 - Voucher đã được gán cho user trước đó
                $isSaved = $this->voucherWalletRepository->isSavedByCustomer((string) $userId, (string) $dto->voucherId);
                if ($isSaved) {
                    $alreadyAssignedCount++;
                    continue;
                }

                // Lưu vào ví người dùng
                $success = $this->voucherWalletRepository->saveToWallet((string) $userId, (string) $dto->voucherId);
                if ($success) {
                    $count++;
                    event(new \App\Modules\Finance\Events\VoucherAssigned((string) $dto->voucherId, (string) $userId));
                }
            }

            if ($count === 0 && $alreadyAssignedCount > 0) {
                $this->throw('Người dùng đã có voucher này.');
            }

            return "Gán voucher cho người dùng thành công. ({$count} thành công, {$alreadyAssignedCount} bỏ qua)";
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function deactivate(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id): string {
            /** @var Voucher|null $voucher */
            $voucher = $this->voucherRepository->findById($id);
            
            // A2 - Voucher không tồn tại
            $this->validate($voucher !== null, 'Voucher không tồn tại.', 404);

            // A3 - Voucher đã ở trạng thái Inactive
            $this->validate($voucher->is_active === true, 'Voucher đã được vô hiệu hóa trước đó.');

            $success = $this->voucherRepository->updateById($id, ['is_active' => false]);
            $this->validate($success !== false, 'Vô hiệu hóa voucher thất bại.');

            event(new \App\Modules\Finance\Events\VoucherDeactivated((string) $id));

            return 'Voucher đã được vô hiệu hóa thành công.';
        }, useTransaction: true);
    }
}
