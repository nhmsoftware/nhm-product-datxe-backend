<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\AssignVoucherDTO;
use App\Modules\Finance\DTO\CreateVoucherDTO;
use App\Modules\Finance\DTO\UpdateVoucherDTO;
use App\Modules\Finance\Http\Requests\AdminAssignVoucherRequest;
use App\Modules\Finance\Http\Requests\AdminCreateVoucherRequest;
use App\Modules\Finance\Http\Requests\AdminListVoucherRequest;
use App\Modules\Finance\Http\Requests\AdminUpdateVoucherRequest;
use App\Modules\Finance\Interfaces\AdminVoucherServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class AdminVoucherController extends BaseController
{
    public function __construct(
        private readonly AdminVoucherServiceInterface $adminVoucherService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/finance/vouchers',
        summary: 'Danh sách voucher (Admin) (UC-99)',
        security: [['sanctum' => []]],
        tags: ['Admin - Finance']
    )]
    #[OA\Parameter(name: 'code', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(response: 200, description: 'Thành công')]
    public function index(AdminListVoucherRequest $request): JsonResponse
    {
        $result = $this->adminVoucherService->listVouchers($request->validated());
        return $this->sendSuccess($result->getData(), 'Lấy danh sách voucher thành công.');
    }

    #[OA\Get(
        path: '/api/v1/admin/finance/vouchers/{id}',
        summary: 'Chi tiết voucher (Admin) (UC-99)',
        security: [['sanctum' => []]],
        tags: ['Admin - Finance']
    )]
    #[OA\Response(response: 200, description: 'Thành công')]
    public function show(string $id): JsonResponse
    {
        $result = $this->adminVoucherService->getVoucherDetail($id);
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Lấy chi tiết voucher thành công.');
    }

    #[OA\Post(
        path: '/api/v1/admin/finance/vouchers',
        summary: 'Tạo voucher mới (Admin) (UC-99)',
        security: [['sanctum' => []]],
        tags: ['Admin - Finance']
    )]
    #[OA\Response(response: 201, description: 'Tạo thành công')]
    public function store(AdminCreateVoucherRequest $request): JsonResponse
    {
        $result = $this->adminVoucherService->createVoucher(CreateVoucherDTO::fromRequest($request));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Tạo voucher thành công.', 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/finance/vouchers/{id}',
        summary: 'Cập nhật voucher (Admin) (UC-99)',
        security: [['sanctum' => []]],
        tags: ['Admin - Finance']
    )]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function update(AdminUpdateVoucherRequest $request, string $id): JsonResponse
    {
        $result = $this->adminVoucherService->updateVoucher($id, UpdateVoucherDTO::fromRequest($request));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Cập nhật voucher thành công.');
    }

    #[OA\Delete(
        path: '/api/v1/admin/finance/vouchers/{id}',
        summary: 'Xóa voucher (Admin) (UC-99)',
        security: [['sanctum' => []]],
        tags: ['Admin - Finance']
    )]
    #[OA\Response(response: 200, description: 'Xóa thành công')]
    public function destroy(string $id): JsonResponse
    {
        $result = $this->adminVoucherService->deleteVoucher($id);
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess(null, 'Xóa voucher thành công.');
    }

    #[OA\Post(
        path: '/api/v1/admin/finance/vouchers/assign',
        summary: 'Gán voucher cho người dùng (Admin) (UC-99)',
        security: [['sanctum' => []]],
        tags: ['Admin - Finance']
    )]
    #[OA\Response(response: 200, description: 'Gán thành công')]
    public function assign(AdminAssignVoucherRequest $request): JsonResponse
    {
        $result = $this->adminVoucherService->assignVoucher(AssignVoucherDTO::fromRequest($request));
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess(message: $result->getData());
    }

    #[OA\Patch(
        path: '/api/v1/admin/finance/vouchers/{id}/deactivate',
        summary: 'Vô hiệu hóa voucher (Admin) (UC-102)',
        security: [['sanctum' => []]],
        tags: ['Admin - Finance']
    )]
    #[OA\Response(response: 200, description: 'Vô hiệu hóa thành công')]
    public function deactivate(string $id): JsonResponse
    {
        $result = $this->adminVoucherService->deactivate($id);
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess(message: $result->getData());
    }
}
