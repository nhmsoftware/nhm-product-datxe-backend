<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\Services\VehicleTypeCatalogService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class VehicleTypeController extends BaseController
{
    public function __construct(
        private readonly VehicleTypeCatalogService $vehicleTypeCatalogService
    ) {}

    #[OA\Get(
        path: '/api/v1/meta/vehicle-types',
        summary: 'Danh sách loại xe metadata',
        tags: ['Vehicle Types'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công')
        ]
    )]
    public function index(): JsonResponse
    {
        return $this->sendSuccess(
            $this->vehicleTypeCatalogService->listActive(),
            'Lấy danh sách loại xe thành công.'
        );
    }

    public function listAll(): JsonResponse
    {
        return $this->sendSuccess(
            $this->vehicleTypeCatalogService->listAll(),
            'Lấy danh mục loại xe thành công.'
        );
    }

    public function store(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $type = $this->vehicleTypeCatalogService->create($request->all());
            return $this->sendSuccess($type, 'Tạo phương tiện thành công.', 201);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        }
    }

    public function update(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        try {
            $type = $this->vehicleTypeCatalogService->update($id, $request->all());
            return $this->sendSuccess($type, 'Cập nhật phương tiện thành công.');
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $type = $this->vehicleTypeCatalogService->archive($id);
            return $this->sendSuccess($type, 'Lưu trữ phương tiện thành công.');
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        }
    }
}
