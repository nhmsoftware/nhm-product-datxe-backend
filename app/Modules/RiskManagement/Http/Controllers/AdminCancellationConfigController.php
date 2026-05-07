<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\RiskManagement\DTO\CreateCancellationConfigDTO;
use App\Modules\RiskManagement\DTO\UpdateCancellationConfigDTO;
use App\Modules\RiskManagement\Http\Requests\AdminCancellationConfigRequest;
use App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminCancellationConfigController extends BaseController
{
    public function __construct(
        private readonly CancellationConfigServiceInterface $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->listConfigs($request->all());
        return $this->sendSuccess($result->getData(), 'Lấy danh sách cấu hình hủy chuyến thành công.');
    }

    public function store(AdminCancellationConfigRequest $request): JsonResponse
    {
        $dto = CreateCancellationConfigDTO::fromRequest($request);
        $result = $this->service->createConfig($dto);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Cấu hình hủy chuyến thành công.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $result = $this->service->getConfig($id);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy thông tin cấu hình thành công.');
    }

    public function update(string $id, AdminCancellationConfigRequest $request): JsonResponse
    {
        $dto = UpdateCancellationConfigDTO::fromRequest($request);
        $result = $this->service->updateConfig($id, $dto);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess($result->getData(), 'Cập nhật cấu hình hủy chuyến thành công.');
    }

    public function destroy(string $id): JsonResponse
    {
        $result = $this->service->deleteConfig($id);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getStatusCode());
        }

        return $this->sendSuccess(null, 'Xóa cấu hình hủy chuyến thành công.');
    }
}
