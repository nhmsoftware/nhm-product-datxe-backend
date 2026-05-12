<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\ProcessRefundDTO;
use App\Modules\Finance\Interfaces\RefundRepositoryInterface;
use App\Modules\Finance\Interfaces\RefundServiceInterface;
use App\Modules\Finance\Model\Enums\RefundStatus;
use Illuminate\Support\Facades\Event;

final class RefundService extends BaseService implements RefundServiceInterface
{
    public function __construct(
        private readonly RefundRepositoryInterface $refundRepository,
    ) {}

    public function list(array $filters): ServiceReturn
    {
        return $this->execute(function () use ($filters) {
            $paginator = $this->refundRepository->search($filters);
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
            $refund = $this->refundRepository->findWithDetails($id);
            $this->validate($refund !== null, 'Không tìm thấy yêu cầu hoàn tiền.', 404);
            return $refund->toArray();
        });
    }

    public function process(ProcessRefundDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $refund = $this->refundRepository->find($dto->refundId);
            $this->validate($refund !== null, 'Không tìm thấy yêu cầu hoàn tiền.', 404);
            
            // Check current status - can only process if not terminal?
            // Usually can process PENDING, but if APPROVED, can move to COMPLETED
            
            $updateData = [
                'status' => $dto->status->value,
                'admin_note' => $dto->note,
                'processed_by' => $dto->adminId,
                'processed_at' => now(),
            ];

            if ($dto->status === RefundStatus::APPROVED) {
                $this->validate($dto->amount !== null && $dto->amount > 0, 'Số tiền hoàn trả không hợp lệ.', 400);
                $updateData['amount'] = $dto->amount;
            }

            if ($dto->status === RefundStatus::COMPLETED) {
                $updateData['refunded_at'] = now();
            }

            $this->refundRepository->updateById($refund->id, $updateData);

            Event::dispatch(new \App\Modules\Finance\Events\RefundProcessed(
                refundId: (string) $refund->id,
                adminId: $dto->adminId,
                status: $dto->status->value,
                amount: $dto->amount,
                processedAt: now()->toIso8601String()
            ));

            return ['message' => 'Cập nhật trạng thái hoàn tiền thành công'];
        }, useTransaction: true);
    }
}
