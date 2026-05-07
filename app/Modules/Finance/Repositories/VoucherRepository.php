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

    /**
     * @inheritDoc
     */
    public function search(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->query();

        if (!empty($filters['code'])) {
            $query->where('code', 'ilike', '%' . $filters['code'] . '%');
        }

        if (!empty($filters['description'])) {
            $query->where('description', 'ilike', '%' . $filters['description'] . '%');
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['valid_from'])) {
            $query->where('valid_from', '>=', $filters['valid_from']);
        }

        if (!empty($filters['valid_until'])) {
            $query->where('valid_until', '<=', $filters['valid_until']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }
}
