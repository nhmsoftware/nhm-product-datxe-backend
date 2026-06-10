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
}
