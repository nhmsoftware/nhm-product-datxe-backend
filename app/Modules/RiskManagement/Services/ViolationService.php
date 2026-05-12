<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\RiskManagement\Interfaces\UserViolationRepositoryInterface;
use App\Modules\RiskManagement\Interfaces\ViolationServiceInterface;

final class ViolationService extends BaseService implements ViolationServiceInterface
{
    public function __construct(
        private readonly UserViolationRepositoryInterface $userViolationRepository,
        private readonly \App\Modules\User\Interfaces\UserRepositoryInterface $userRepository,
    ) {}

    public function createViolation(string $userId, string $type, string $reason, ?string $complaintId = null, ?string $createdBy = null): ServiceReturn
    {
        return $this->execute(function () use ($userId, $type, $reason, $complaintId, $createdBy) {
            $violation = $this->userViolationRepository->create([
                'user_id' => $userId,
                'type' => $type,
                'reason' => $reason,
                'complaint_id' => $complaintId,
                'created_by' => $createdBy,
            ]);

            // Dispatch event for realtime notification
            event(new \App\Modules\RiskManagement\Events\UserWarned(
                userId: $userId,
                violationId: (string) $violation->id,
                type: $type,
                reason: $reason,
                violationCount: $this->userViolationRepository->countByUserId($userId),
                adminId: $createdBy
            ));
            
            return $violation->toArray();
        }, useTransaction: true);
    }

    public function warnUser(\App\Modules\RiskManagement\DTO\WarnUserDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy người dùng.', 404);
            
            // A3 - Driver already locked
            if (!$user->is_active) {
                return [
                    'message' => 'Người dùng này hiện đang bị khóa tài khoản.',
                    'is_active' => false,
                    'user_id' => $dto->userId
                ];
            }

            $violation = $this->userViolationRepository->create([
                'user_id' => $dto->userId,
                'type' => $dto->type->value,
                'reason' => $dto->reason,
                'complaint_id' => $dto->complaintId,
                'created_by' => $dto->adminId,
            ]);

            $violationCount = $this->userViolationRepository->countByUserId($dto->userId);

            event(new \App\Modules\RiskManagement\Events\UserWarned(
                userId: $dto->userId,
                violationId: (string) $violation->id,
                type: $dto->type->value,
                reason: $dto->reason,
                violationCount: $violationCount,
                adminId: $dto->adminId
            ));

            $result = [
                'message' => 'Gửi cảnh báo thành công.',
                'violation_id' => $violation->id,
                'violation_count' => $violationCount
            ];

            // A4 - Over threshold
            if ($violationCount >= 3) {
                $result['suggestion'] = 'Người dùng này đã vi phạm nhiều lần (' . $violationCount . ' lần). Đề xuất khóa tài khoản hoặc điều tra thêm.';
            }

            return $result;
        }, useTransaction: true);
    }

    public function getHistory(string $userId): ServiceReturn
    {
        return $this->execute(fn() => $this->userViolationRepository->getByUserId($userId)->toArray());
    }
}
