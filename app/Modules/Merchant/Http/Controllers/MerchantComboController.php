<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Merchant\DTO\ComboDTO;
use App\Modules\Merchant\Http\Requests\ManageComboRequest;
use App\Modules\Merchant\Interfaces\ComboServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class MerchantComboController extends BaseController
{
    public function __construct(
        private readonly ComboServiceInterface $comboService
    ) {}

    #[OA\Get(
        path: '/api/v1/merchant/combos',
        summary: 'Lấy danh sách combo của cửa hàng (UC-61)',
        security: [['sanctum' => []]],
        tags: ['Merchant Combo'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $merchantProfileId = (string) $request->user()->merchantProfile->id;
        $result = $this->comboService->getMerchantCombos($merchantProfileId);
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        
        return $this->sendSuccess($result->getData());
    }

    #[OA\Get(
        path: '/api/v1/merchant/combos/{id}',
        summary: 'Xem chi tiết combo (UC-62)',
        security: [['sanctum' => []]],
        tags: ['Merchant Combo'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy combo'),
        ]
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        $merchantProfileId = (string) $request->user()->merchantProfile->id;
        $result = $this->comboService->getComboDetail($id, $merchantProfileId);
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        
        return $this->sendSuccess($result->getData());
    }

    #[OA\Post(
        path: '/api/v1/merchant/combos',
        summary: 'Thêm combo mới (UC-63)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'items'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Combo Gà Rán'),
                    new OA\Property(property: 'description', type: 'string', example: '2 miếng gà + 1 nước'),
                    new OA\Property(property: 'price', type: 'number', example: 50000),
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'menu_item_id', type: 'string'),
                            new OA\Property(property: 'quantity', type: 'integer'),
                        ]
                    )),
                ]
            )
        ),
        tags: ['Merchant Combo'],
        responses: [
            new OA\Response(response: 201, description: 'Tạo thành công'),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(ManageComboRequest $request): JsonResponse
    {
        $merchantProfileId = (string) $request->user()->merchantProfile->id;
        $result = $this->comboService->createCombo(ComboDTO::fromRequest($request, $merchantProfileId));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        
        return $this->sendSuccess($result->getData(), 'Tạo combo thành công.', 201);
    }

    #[OA\Put(
        path: '/api/v1/merchant/combos/{id}',
        summary: 'Cập nhật combo (UC-64)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'items'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'menu_item_id', type: 'string'),
                            new OA\Property(property: 'quantity', type: 'integer'),
                        ]
                    )),
                ]
            )
        ),
        tags: ['Merchant Combo'],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy combo'),
        ]
    )]
    public function update(ManageComboRequest $request, string $id): JsonResponse
    {
        $merchantProfileId = (string) $request->user()->merchantProfile->id;
        $result = $this->comboService->updateCombo($id, ComboDTO::fromRequest($request, $merchantProfileId));
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        
        return $this->sendSuccess($result->getData(), 'Cập nhật combo thành công.');
    }

    #[OA\Delete(
        path: '/api/v1/merchant/combos/{id}',
        summary: 'Xóa combo (UC-65)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        tags: ['Merchant Combo'],
        responses: [
            new OA\Response(response: 200, description: 'Xóa thành công'),
            new OA\Response(response: 403, description: 'Không có quyền'),
        ]
    )]
    public function destroy(Request $request, string $id): JsonResponse
    {
        $merchantProfileId = (string) $request->user()->merchantProfile->id;
        $result = $this->comboService->deleteCombo($id, $merchantProfileId);
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        
        return $this->sendSuccess([], 'Xóa combo thành công.');
    }

    #[OA\Patch(
        path: '/api/v1/merchant/combos/{id}/status',
        summary: 'Thay đổi trạng thái combo (UC-61)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'is_available', type: 'boolean')
                ]
            )
        ),
        tags: ['Merchant Combo'],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật trạng thái thành công'),
        ]
    )]
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $merchantProfileId = (string) $request->user()->merchantProfile->id;
        $isAvailable = (bool) $request->input('is_available');
        
        $result = $this->comboService->updateStatus($id, $merchantProfileId, $isAvailable);
        
        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        
        return $this->sendSuccess([], 'Cập nhật trạng thái combo thành công.');
    }
}
