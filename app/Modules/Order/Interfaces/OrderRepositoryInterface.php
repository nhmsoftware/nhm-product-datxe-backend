<?php

declare(strict_types=1);

namespace App\Modules\Order\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Order\DTO\GetOrderHistoryFilterDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get unified order history for a customer
     *
     * @param GetOrderHistoryFilterDTO $filters
     * @return LengthAwarePaginator
     */
    public function getHistory(GetOrderHistoryFilterDTO $filters): LengthAwarePaginator;
}
