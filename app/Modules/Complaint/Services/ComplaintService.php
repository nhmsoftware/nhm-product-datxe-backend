<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Complaint\DTO\HandleComplaintDTO;
use App\Modules\Complaint\Events\ComplaintHandled;
use App\Modules\Complaint\Interfaces\ComplaintRepositoryInterface;
use App\Modules\Complaint\Interfaces\ComplaintServiceInterface;
use App\Modules\Complaint\Model\Enums\ComplaintResolutionAction;
use App\Modules\Complaint\Model\Enums\ComplaintStatus;
use App\Modules\RiskManagement\Interfaces\ViolationServiceInterface;

final class ComplaintService extends BaseService implements ComplaintServiceInterface
{
    public function __construct(
        private readonly ComplaintRepositoryInterface $complaintRepository,
        private readonly ViolationServiceInterface $violationService,
    ) {}

    public function list(array $filters): ServiceReturn
    {
        return $this->execute(function () use ($filters) {
            $paginator = $this->complaintRepository->search($filters);
            return [
                'items' => $paginator->items(),
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ];
        });
    }

    public function detail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $complaint = $this->complaintRepository->findWithDetails($id);
            $this->validate($complaint !== null, 'Không tìm thấy khiếu nại.', 404);
            return $complaint->toArray();
        });
    }

    public function handle(HandleComplaintDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $complaint = $this->complaintRepository->find($dto->complaintId);
            $this->validate($complaint !== null, 'Không tìm thấy khiếu nại.', 404);
            
            // Check if already processed? Maybe allowed to re-process?
            // Usually we only process PENDING or WAITING_FOR_INFO
            
            $updateData = [
                'resolution_action' => $dto->action->value,
                'admin_note' => $dto->note,
                'processed_by' => $dto->adminId,
                'processed_at' => now(),
            ];

            switch ($dto->action) {
                case ComplaintResolutionAction::REFUND:
                    $updateData['status'] = ComplaintStatus::PROCESSING->value; // Refund Processing as per UC A4
                    break;
                case ComplaintResolutionAction::WARN_DRIVER:
                    $updateData['status'] = ComplaintStatus::RESOLVED->value;
                    $this->handleWarnDriver($complaint, $dto);
                    break;
                case ComplaintResolutionAction::WARN_CUSTOMER:
                    $updateData['status'] = ComplaintStatus::RESOLVED->value;
                    $this->handleWarnCustomer($complaint, $dto);
                    break;
                case ComplaintResolutionAction::REJECT:
                    $updateData['status'] = ComplaintStatus::REJECTED->value;
                    break;
                case ComplaintResolutionAction::REQUEST_INFO:
                    $updateData['status'] = ComplaintStatus::WAITING_FOR_INFO->value;
                    break;
            }

            $this->complaintRepository->updateById($complaint->id, $updateData);

            event(new ComplaintHandled(
                complaintId: (string) $complaint->id,
                adminId: $dto->adminId,
                action: $dto->action->value,
                note: $dto->note,
                processedAt: now()->toIso8601String()
            ));

            return ['message' => 'Xử lý khiếu nại thành công'];
        }, useTransaction: true);
    }

    private function handleWarnDriver($complaint, HandleComplaintDTO $dto): void
    {
        // Try to find driver ID from the related ride/order
        $driverId = null;
        $complaintable = $complaint->complaintable;
        
        if ($complaintable && isset($complaintable->driver_id)) {
            $driverId = (string) $complaintable->driver_id;
        }

        if ($driverId) {
            $this->violationService->createViolation(
                userId: $driverId,
                type: 'WARNING',
                reason: 'Cảnh báo từ khiếu nại #' . $complaint->id . ': ' . $dto->note,
                complaintId: (string) $complaint->id,
                createdBy: $dto->adminId
            );
        }
    }

    private function handleWarnCustomer($complaint, HandleComplaintDTO $dto): void
    {
        // Try to find customer ID from the related ride/order
        $customerId = null;
        $complaintable = $complaint->complaintable;
        
        if ($complaintable && isset($complaintable->customer_id)) {
            $customerId = (string) $complaintable->customer_id;
        }

        if ($customerId) {
            $this->violationService->createViolation(
                userId: $customerId,
                type: 'WARNING',
                reason: 'Cảnh báo từ khiếu nại #' . $complaint->id . ': ' . $dto->note,
                complaintId: (string) $complaint->id,
                createdBy: $dto->adminId
            );
        }
    }
}
