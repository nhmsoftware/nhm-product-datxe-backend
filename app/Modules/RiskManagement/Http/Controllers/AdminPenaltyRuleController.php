<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Http\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\RiskManagement\DTO\CreatePenaltyRuleDTO;
use App\Modules\RiskManagement\DTO\UpdatePenaltyRuleDTO;
use App\Modules\RiskManagement\Http\Requests\AdminPenaltyRuleRequest;
use App\Modules\RiskManagement\Interfaces\PenaltyRuleServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminPenaltyRuleController extends BaseController
{
    public function __construct(
        private readonly PenaltyRuleServiceInterface $penaltyRuleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->penaltyRuleService->listRules($request->all());
        return $this->sendSuccess($result->getData(), 'Lấy danh sách quy tắc xử phạt thành công.');
    }

    public function store(AdminPenaltyRuleRequest $request): JsonResponse
    {
        $dto = CreatePenaltyRuleDTO::fromRequest($request);
        $result = $this->penaltyRuleService->createRule($dto);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Cấu hình quy tắc xử phạt thành công.');
    }

    public function show(string $id): JsonResponse
    {
        $result = $this->penaltyRuleService->getRule($id);
        
        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy thông tin quy tắc thành công.');
    }

    public function update(string $id, AdminPenaltyRuleRequest $request): JsonResponse
    {
        $dto = UpdatePenaltyRuleDTO::fromRequest($request);
        $result = $this->penaltyRuleService->updateRule($id, $dto);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật quy tắc xử phạt thành công.');
    }

    public function destroy(string $id): JsonResponse
    {
        $result = $this->penaltyRuleService->deleteRule($id);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess(null, 'Xóa quy tắc xử phạt thành công.');
    }

    public function toggleStatus(string $id, Request $request): JsonResponse
    {
        $isActive = $request->boolean('is_active');
        $result = $this->penaltyRuleService->toggleStatus($id, $isActive);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess(null, 'Cập nhật trạng thái thành công.');
    }
}
