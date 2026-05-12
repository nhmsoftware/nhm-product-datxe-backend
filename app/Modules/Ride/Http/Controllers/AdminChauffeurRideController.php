<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\Http\Resources\AdminChauffeurRideResource;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controller quản lý dịch vụ Lái hộ dành cho Admin.
 */
final class AdminChauffeurRideController extends BaseController
{
    public function __construct(
        private readonly RideServiceInterface $rideService
    ) {}

    /**
     * UC-124: Danh sách các chuyến xe Lái hộ.
     */
    #[OA\Get(
        path: '/api/v1/admin/chauffeur/rides',
        summary: 'Danh sách chuyến xe Lái hộ (Admin)',
        description: 'Lấy toàn bộ danh sách các chuyến xe Lái hộ (RideType: 5).',
        security: [['sanctum' => []]],
        tags: ['Admin Chauffeur'],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', description: 'Tìm theo ID, địa chỉ hoặc SĐT khách', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Trạng thái (waiting, assigned, completed, canceled)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền Admin')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->rideService->listChauffeurRidesForAdmin($request->all());
        
        $data = $result->getData();

        if ($data === null) {
            return $this->sendSuccess([], 'Không có dữ liệu.');
        }
        
        $resource = AdminChauffeurRideResource::collection($data);

        return response()->json([
            'success' => true,
            'data'    => $resource->response()->getData(true),
            'message' => 'Lấy danh sách chuyến Lái hộ thành công.'
        ]);
    }
}
