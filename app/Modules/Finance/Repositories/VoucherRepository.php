<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\VoucherRepositoryInterface;
use App\Modules\Finance\Model\Voucher;
use Illuminate\Support\Collection;

final class VoucherRepository extends BaseRepository implements VoucherRepositoryInterface
{
    public function getModel(): string
    {
        return Voucher::class;
    }

    /**
     * @inheritDoc
     */
    public function findAllActive(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->where('valid_until', '>=', now())
            ->orderBy('valid_until', 'asc')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function findByCode(string $code): ?Voucher
    {
        /** @var Voucher|null */
        return $this->model->where('code', $code)->first();
    }
}
