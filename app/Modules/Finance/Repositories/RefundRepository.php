<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\RefundRepositoryInterface;
use App\Modules\Finance\Model\RefundRequest;
use Illuminate\Pagination\LengthAwarePaginator;

final class RefundRepository extends BaseRepository implements RefundRepositoryInterface
{
    public function getModel(): string
    {
        return RefundRequest::class;
    }

    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->getQuery();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['refundable_id'])) {
            $query->where('refundable_id', $filters['refundable_id']);
        }

        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('id', 'like', '%' . $filters['keyword'] . '%')
                  ->orWhere('reason', 'like', '%' . $filters['keyword'] . '%');
            });
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->with(['customer'])->latest()->paginate($perPage);
    }

    public function findWithDetails(string $id): ?RefundRequest
    {
        /** @var RefundRequest|null */
        return $this->model->with(['customer', 'refundable', 'processor'])->find($id);
    }
}
