<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\Http\Resources\AdminServiceOrderResource;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controller quản lý đơn dịch vụ (Giao hàng, Đồ ăn) dành cho Admin.
 * Tách biệt hoàn toàn với AdminScheduledRideController (chuyến xe hành khách).
 */
final class AdminServiceOrderController extends BaseController
{
    public function __construct(
        private readonly RideServiceInterface $rideService
    ) {}

    /**
     * Danh sách đơn dịch vụ cho Admin quản lý.
     */
    #[OA\Get(
        path: '/api/v1/admin/services',
        summary: 'Danh sách đơn dịch vụ (Admin)',
        description: 'Lấy danh sách tất cả đơn giao hàng và đặt đồ ăn. Hỗ trợ lọc theo status, ride_type, keyword và phân trang.',
        security: [['sanctum' => []]],
        tags: ['Admin Services'],
        parameters: [
            new OA\Parameter(name: 'keyword',  in: 'query', description: 'Tìm theo ID, địa chỉ hoặc SĐT khách', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status',   in: 'query', description: 'Trạng thái (waiting, assigned, completed, canceled)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ride_type',in: 'query', description: 'Loại dịch vụ: 4=Giao hàng, 6=Đồ ăn', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page',     in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'no_pagination', in: 'query', description: 'Lấy toàn bộ không phân trang (truyền 1)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền Admin'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->rideService->listServiceOrdersForAdmin($request->all());

        $data = $result->getData();

        if ($data === null) {
            return $this->sendSuccess([], 'Không có dữ liệu.');
        }

        $resource = AdminServiceOrderResource::collection($data);

        return response()->json([
            'success' => true,
            'data'    => $resource->response()->getData(true),
            'message' => 'Lấy danh sách đơn dịch vụ thành công.',
        ]);
    }

    /**
     * Chi tiết một đơn dịch vụ.
     */
    #[OA\Get(
        path: '/api/v1/admin/services/{id}',
        summary: 'Chi tiết đơn dịch vụ (Admin)',
        security: [['sanctum' => []]],
        tags: ['Admin Services'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        // Lấy danh sách dịch vụ filter theo keyword = id để tìm chính xác
        $result = $this->rideService->listServiceOrdersForAdmin([
            'keyword'       => $id,
            'no_pagination' => 1,
        ]);

        $data = $result->getData();

        if ($data === null || (is_object($data) && method_exists($data, 'isEmpty') && $data->isEmpty())) {
            return $this->sendError('Không tìm thấy đơn dịch vụ.', 404);
        }

        // Tìm chính xác theo ID
        $order = is_iterable($data)
            ? collect($data)->firstWhere('id', $id)
            : null;

        if ($order === null) {
            return $this->sendError('Không tìm thấy đơn dịch vụ.', 404);
        }

        return $this->sendSuccess(
            new AdminServiceOrderResource($order),
            'Lấy chi tiết đơn dịch vụ thành công.'
        );
    }
}
