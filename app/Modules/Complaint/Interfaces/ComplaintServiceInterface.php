<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Complaint\DTO\HandleComplaintDTO;

interface ComplaintServiceInterface
{
    /**
     * Get list of complaints with filters
     * UC-108
     */
    public function list(array $filters): ServiceReturn;

    /**
     * Get detail of a complaint
     * UC-108
     */
    public function detail(string $id): ServiceReturn;

    /**
     * Handle a complaint (Refund, Warn, Reject, etc.)
     * UC-108
     */
    public function handle(HandleComplaintDTO $dto): ServiceReturn;
}
