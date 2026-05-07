<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\Interfaces\MerchantRegistrationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * @OA\Tag(name="Admin|Merchant", description="Quản lý Merchant từ phía Admin")
 */
final class AdminMerchantController extends BaseController
{
    public function __construct(
        private readonly MerchantRegistrationServiceInterface $registrationService,
    ) {}

    /**
     * Danh sách hồ sơ đăng ký Merchant đang chờ duyệt.
     */
    #[OA\Get(
        path: '/api/v1/admin/merchant/applications',
        summary: 'Danh sách hồ sơ Merchant chờ duyệt',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function index(): JsonResponse
    {
        $result = $this->registrationService->getApplications();
        return $this->sendSuccess($result->getData());
    }

    /**
     * Chi tiết hồ sơ đăng ký Merchant.
     */
    #[OA\Get(
        path: '/api/v1/admin/merchant/applications/{id}',
        summary: 'Chi tiết hồ sơ đăng ký Merchant',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->registrationService->getApplicationDetails($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData());
    }

    /**
     * Duyệt hồ sơ Merchant.
     */
    #[OA\Post(
        path: '/api/v1/admin/merchant/applications/{id}/approve',
        summary: 'Duyệt hồ sơ Merchant',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Duyệt thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy hồ sơ'),
        ]
    )]
    public function approve(string $id): JsonResponse
    {
        $result = $this->registrationService->approveRegistration($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    /**
     * Từ chối hồ sơ Merchant.
     */
    #[OA\Post(
        path: '/api/v1/admin/merchant/applications/{id}/reject',
        summary: 'Từ chối hồ sơ Merchant',
        security: [['sanctum' => []]],
        tags: ['Admin|Merchant'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: 'Tài liệu không rõ ràng')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Từ chối thành công'),
        ]
    )]
    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);
        
        $result = $this->registrationService->rejectRegistration($id, $request->input('reason'));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
