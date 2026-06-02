<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\PaymentMethodRepositoryInterface;
use App\Modules\Finance\Model\PaymentMethod;
use App\Modules\Finance\Model\Enums\PaymentMethodType;
use Illuminate\Support\Collection;

final class PaymentMethodRepository extends BaseRepository implements PaymentMethodRepositoryInterface
{
    public function getModel(): string
    {
        return PaymentMethod::class;
    }

    /**
     * Lấy tất cả phương thức thanh toán Active, sắp xếp theo sort_order.
     */
    public function getActiveMethods(): Collection
    {
        /** @var Collection<int, PaymentMethod> */
        return $this->getQuery()->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Tìm phương thức Active theo code.
     */
    public function findActiveByCode(string $code): ?PaymentMethod
    {
        /** @var PaymentMethod|null */
        return $this->getQuery()->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Lấy tài khoản nhận chuyển khoản đang Active.
     */
    public function findActiveTransferAccount(): ?PaymentMethod
    {
        /** @var PaymentMethod|null */
        return $this->getQuery()->where('type', PaymentMethodType::BANK_TRANSFER->value)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Lấy tất cả phương thức (kể cả inactive) cho Admin.
     */
    public function getAllForAdmin(): Collection
    {
        /** @var Collection<int, PaymentMethod> */
        return $this->getQuery()->orderBy('sort_order')->get();
    }
}
