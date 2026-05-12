<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\RefundRequest;
use Illuminate\Pagination\LengthAwarePaginator;

interface RefundRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and filter refund requests
     * UC-109
     */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Find refund request with customer and refundable relations
     * UC-109
     */
    public function findWithDetails(string $id): ?RefundRequest;
}
