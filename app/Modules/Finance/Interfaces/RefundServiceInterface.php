<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\ProcessRefundDTO;

interface RefundServiceInterface
{
    /**
     * List refund requests with filters
     * UC-109
     */
    public function list(array $filters): ServiceReturn;

    /**
     * Get detail of a refund request
     * UC-109
     */
    public function detail(string $id): ServiceReturn;

    /**
     * Process refund (Approve, Reject, Complete)
     * UC-109
     */
    public function process(ProcessRefundDTO $dto): ServiceReturn;
}
