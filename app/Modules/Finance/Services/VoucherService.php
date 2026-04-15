<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\ApplyVoucherQuickDTO;
use App\Modules\Finance\DTO\VoucherDTO;
use App\Modules\Finance\Interfaces\VoucherRepositoryInterface;
use App\Modules\Finance\Interfaces\VoucherWalletRepositoryInterface;
use App\Modules\Finance\Interfaces\VoucherServiceInterface;
use App\Modules\Finance\Model\Enums\VoucherServiceType;
use App\Modules\Finance\Model\Voucher;

final class VoucherService extends BaseService implements VoucherServiceInterface
{
    public function __construct(
        private readonly VoucherRepositoryInterface $voucherRepository,
        private readonly VoucherWalletRepositoryInterface $voucherWalletRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function listVouchers(int $customerId, ?string $serviceType = null): ServiceReturn
    {
        return $this->execute(function () use ($customerId, $serviceType): array {
            $vouchers = $this->voucherRepository->findAllActive();

            // Lọc theo service type nếu có
            if ($serviceType !== null) {
                $type = match (strtolower($serviceType)) {
                    'ride' => [VoucherServiceType::RIDE, VoucherServiceType::BOTH],
                    'food' => [VoucherServiceType::FOOD, VoucherServiceType::BOTH],
                    default => [VoucherServiceType::BOTH],
                };

                $vouchers = $vouchers->filter(fn(Voucher $v) => in_array($v->service_type, $type));
            }

            return $vouchers->map(function (Voucher $v) use ($customerId) {
                $isSaved = $this->voucherWalletRepository->isSavedByCustomer($customerId, (int) $v->id);
                return VoucherDTO::fromModel($v, $isSaved)->toArray();
            })->values()->toArray();
        });
    }

    /**
     * @inheritDoc
     */
    public function getVoucherDetail(int $customerId, int $voucherId): ServiceReturn
    {
        return $this->execute(function () use ($customerId, $voucherId): array {
            /** @var Voucher|null $voucher */
            $voucher = $this->voucherRepository->findById($voucherId);
            $this->validate($voucher !== null, 'Voucher không tồn tại.', 404);

            $isSaved = $this->voucherWalletRepository->isSavedByCustomer($customerId, $voucherId);
            return VoucherDTO::fromModel($voucher, $isSaved)->toArray();
        });
    }

    /**
     * @inheritDoc
     */
    public function saveVoucher(int $customerId, int $voucherId): ServiceReturn
    {
        return $this->execute(function () use ($customerId, $voucherId): string {
            /** @var Voucher|null $voucher */
            $voucher = $this->voucherRepository->findById($voucherId);
            $this->validate($voucher !== null, 'Voucher không tồn tại.', 404);
            $this->validate($voucher->isValid(), 'Voucher không khả dụng hoặc đã hết hạn.');

            $alreadySaved = $this->voucherWalletRepository->isSavedByCustomer($customerId, $voucherId);
            if ($alreadySaved) {
                $this->throw('Voucher đã có trong ví của bạn.');
            }

            $success = $this->voucherWalletRepository->saveToWallet($customerId, $voucherId);
            $this->validate($success, 'Không thể lưu voucher vào ví.');

            return 'Lưu voucher vào ví thành công.';
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function applyVoucherQuick(ApplyVoucherQuickDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            /** @var Voucher|null $voucher */
            $voucher = $this->voucherRepository->findById($dto->voucherId);

            // A1 - Voucher không tồn tại hoặc không còn khả dụng
            $this->validate($voucher !== null && $voucher->isValid(), 'Voucher không còn khả dụng.', 400);

            // A2 - Voucher không xác định được loại dịch vụ màn hình đích
            $targetScreen = $voucher->service_type->getTargetScreen();
            $this->validate(!empty($targetScreen), 'Không thể áp dụng voucher.', 400);

            $isSaved = $this->voucherWalletRepository->isSavedByCustomer($dto->customerId, $dto->voucherId);

            return [
                'target_screen' => $targetScreen,
                'voucher' => VoucherDTO::fromModel($voucher, $isSaved)->toArray()
            ];
        });
    }
}
