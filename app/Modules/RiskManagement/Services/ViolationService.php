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

    public function getAllViolations(int $page = 1, int $perPage = 20): ServiceReturn
    {
        return $this->execute(function() use ($page, $perPage) {
            $query = \App\Modules\RiskManagement\Model\UserViolation::with('user');
            $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
            
            $items = collect($paginator->items())->map(function ($violation) {
                // Determine violation count for this user
                $count = \App\Modules\RiskManagement\Model\UserViolation::where('user_id', $violation->user_id)
                            ->where('created_at', '<=', $violation->created_at)
                            ->count();
                
                // Determine status based on count
                $status = 'WARNED';
                if ($count >= 3) {
                    $status = 'SUSPENDED';
                }
                
                return [
                    'id' => $violation->id,
                    'subject' => $violation->user ? ($violation->user->full_name ?: 'Người dùng vô danh') : 'Người dùng vô danh',
                    'role' => $violation->user ? ($violation->user->role?->value === 2 ? 'Driver' : 'Customer') : 'Unknown',
                    'phone' => $violation->user ? $violation->user->phone : '',
                    'type' => $violation->type,
                    'reason' => $violation->reason,
                    'count' => $count,
                    'created_at' => $violation->created_at->format('Y-m-d H:i'),
                    'status' => $status,
                    'ride_id' => 'N/A'
                ];
            });

            return [
                'data' => $items,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            ];
        });
    }
}
