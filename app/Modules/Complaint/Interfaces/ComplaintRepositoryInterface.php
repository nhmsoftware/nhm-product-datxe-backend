<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Complaint\Model\Complaint;
use Illuminate\Pagination\LengthAwarePaginator;

interface ComplaintRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and filter complaints
     * UC-108
     */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Find complaint with relations
     * UC-108
     */
    public function findWithDetails(string $id): ?Complaint;
}
