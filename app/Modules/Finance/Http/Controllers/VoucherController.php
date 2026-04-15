<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\ApplyVoucherQuickDTO;
use App\Modules\Finance\Http\Requests\ApplyVoucherQuickRequest;
use App\Modules\Finance\Interfaces\VoucherServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class VoucherController extends BaseController
{
    public function __construct(
        private readonly VoucherServiceInterface $voucherService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/vouchers',
        description: 'Lấy danh sách các voucher đang hoạt động, có thể lọc theo loại dịch vụ (ride/food).',
        summary: 'Xem danh sách voucher (UC-21)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Parameter(name: 'service_type', description: 'Loại dịch vụ (ride hoặc food)', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Danh sách voucher')]
    public function index(Request $request): JsonResponse
    {
        $result = $this->voucherService->listVouchers(
            (int) $request->user()->id,
            $request->query('service_type')
        );

        return $this->sendSuccess($result->getData(), 'Lấy danh sách voucher thành công.');
    }

    #[OA\Get(
        path: '/api/v1/vouchers/{id}',
        description: 'Xem chi tiết điều kiện áp dụng và mô tả của một voucher cụ thể.',
        summary: 'Xem chi tiết voucher (UC-21)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Parameter(name: 'id', description: 'ID của voucher', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Chi tiết voucher')]
    #[OA\Response(response: 404, description: 'Không tìm thấy voucher')]
    public function show(Request $request, int $id): JsonResponse
    {
        $result = $this->voucherService->getVoucherDetail((int) $request->user()->id, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy chi tiết voucher thành công.');
    }

    #[OA\Post(
        path: '/api/v1/vouchers/{id}/save',
        description: 'Lưu voucher vào ví cá nhân để sử dụng sau.',
        summary: 'Lưu voucher (UC-21 A5)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Parameter(name: 'id', description: 'ID của voucher', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Đã lưu voucher thành công')]
    public function save(Request $request, int $id): JsonResponse
    {
        $result = $this->voucherService->saveVoucher((int) $request->user()->id, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }
        return $this->sendSuccess(message: $result->getData());
    }

    #[OA\Post(
        path: '/api/v1/vouchers/{id}/apply-quick',
        description: 'Kiểm tra voucher và trả về màn hình đích tương ứng (Ride/Food/Delivery) để chuyển hướng.',
        summary: 'Áp dụng nhanh voucher (UC-22)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Parameter(name: 'id', description: 'ID của voucher', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Thông tin chuyển hướng và voucher')]
    public function applyQuick(ApplyVoucherQuickRequest $request): JsonResponse
    {
        $result = $this->voucherService->applyVoucherQuick(
            ApplyVoucherQuickDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Sẵn sàng áp dụng voucher.');
    }
}
