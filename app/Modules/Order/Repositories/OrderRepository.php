<?php

declare(strict_types=1);

namespace App\Modules\Order\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Order\DTO\GetOrderHistoryFilterDTO;
use App\Modules\Order\Interfaces\OrderRepositoryInterface;
use App\Modules\Order\Model\CustomerOrder;
use Illuminate\Pagination\LengthAwarePaginator;

final class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function getModel(): string
    {
        return CustomerOrder::class;
    }

    public function getHistory(GetOrderHistoryFilterDTO $filters): LengthAwarePaginator
    {
        $query = $this->getQuery()
            ->where('customer_id', $filters->customerId);

        if ($filters->serviceType) {
            $query->where('service_type', $filters->serviceType);
        }

        if ($filters->status !== null) {
            $query->where('status', (int) $filters->status);
        }

        if ($filters->startDate !== null) {
            $query->whereDate('created_at', '>=', $filters->startDate);
        }
        if ($filters->endDate !== null) {
            $query->whereDate('created_at', '<=', $filters->endDate);
        }

        $query->orderByDesc('created_at');

        return $query->paginate($filters->perPage);
    }
}
