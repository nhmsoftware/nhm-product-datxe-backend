<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\InitiateTopUpDTO;
use App\Modules\Finance\DTO\ManageWalletDTO;
use App\Modules\Finance\DTO\ViewCreditWalletDTO;
use App\Modules\Finance\DTO\WalletTransactionDetailDTO;
use App\Modules\Finance\Http\Requests\InitiateTopUpRequest;
use App\Modules\Finance\Http\Requests\ViewCreditWalletRequest;
use App\Modules\Finance\Interfaces\WalletServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class WalletController extends BaseController
{
    public function __construct(
        private readonly WalletServiceInterface $walletService
    ) {}

    #[OA\Get(
        path: '/api/v1/finance/wallet/manage',
        description: 'Lấy thông tin tổng quan về ví của tài xế bao gồm số dư, thu nhập và giao dịch gần đây.',
        summary: 'Xem tổng quan ví (UC-43)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Response(
        response: 200,
        description: 'Tải thông tin ví thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'driver_status', properties: [
                    new OA\Property(property: 'is_online', type: 'boolean', example: true),
                    new OA\Property(property: 'label', type: 'string', example: 'Trực tuyến'),
                ], type: 'object'),
                new OA\Property(property: 'wallet', ref: '#/components/schemas/WalletResponse'),
                new OA\Property(property: 'recent_transactions', type: 'array', items: new OA\Items(ref: '#/components/schemas/WalletTransactionResponse')),
            ]
        )
    )]
    public function manage(Request $request): JsonResponse
    {
        $result = $this->walletService->getManageWalletData(
            ManageWalletDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tải thông tin ví thành công.');
    }

    #[OA\Get(
        path: '/api/v1/finance/wallet/credit',
        description: 'Xem chi tiết số dư ví tín dụng và lịch sử giao dịch phân trang.',
        summary: 'Xem ví tín dụng & lịch sử (UC-44)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Parameter(name: 'page', description: 'Trang hiện tại', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'limit', description: 'Số bản ghi mỗi trang', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10))]
    #[OA\Response(
        response: 200,
        description: 'Tải thông tin ví tín dụng thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'wallet', properties: [
                    new OA\Property(property: 'balance', type: 'number', format: 'float', example: 150000),
                    new OA\Property(property: 'total_top_up', type: 'number', format: 'float', example: 500000),
                    new OA\Property(property: 'total_used', type: 'number', format: 'float', example: 350000),
                ], type: 'object'),
                new OA\Property(property: 'history', properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WalletTransactionResponse')),
                    new OA\Property(property: 'meta', properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 5),
                        new OA\Property(property: 'per_page', type: 'integer', example: 10),
                        new OA\Property(property: 'total', type: 'integer', example: 50),
                    ], type: 'object'),
                ], type: 'object'),
            ]
        )
    )]
    public function viewCreditWallet(ViewCreditWalletRequest $request): JsonResponse
    {
        $result = $this->walletService->viewCreditWallet(
            ViewCreditWalletDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tải thông tin ví tín dụng thành công.');
    }

    #[OA\Get(
        path: '/api/v1/finance/wallet/transactions/{transactionId}',
        description: 'Xem chi tiết một giao dịch ví cụ thể.',
        summary: 'Chi tiết giao dịch (UC-44)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Parameter(name: 'transactionId', description: 'ID giao dịch', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Tải chi tiết giao dịch thành công',
        content: new OA\JsonContent(ref: '#/components/schemas/WalletTransactionResponse')
    )]
    #[OA\Response(response: 404, description: 'Không tìm thấy giao dịch')]
    public function getTransactionDetail(int $transactionId, Request $request): JsonResponse
    {
        $result = $this->walletService->getTransactionDetail(
            WalletTransactionDetailDTO::fromRequest($request, (string) $transactionId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tải chi tiết giao dịch thành công.');
    }

    #[OA\Post(
        path: '/api/v1/finance/wallet/top-up',
        description: 'Khởi tạo yêu cầu nạp tiền vào ví tín dụng qua cổng thanh toán.',
        summary: 'Nạp tiền (UC-45)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['amount', 'payment_method'],
            properties: [
                new OA\Property(property: 'amount', type: 'number', example: 50000),
                new OA\Property(property: 'payment_method', type: 'string', example: 'momo'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Khởi tạo giao dịch nạp tiền thành công',
        content: new OA\JsonContent(ref: '#/components/schemas/TopUpResponse')
    )]
    public function initiateTopUp(InitiateTopUpRequest $request): JsonResponse
    {
        $result = $this->walletService->initiateTopUp(
            InitiateTopUpDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Khởi tạo giao dịch nạp tiền thành công.');
    }

    #[OA\Post(
        path: '/api/v1/finance/wallet/top-up/callback',
        description: 'Nhận phản hồi từ cổng thanh toán để cập nhật trạng thái nạp tiền (Dùng cho Gateway callback hoặc Mock).',
        summary: 'Callback nạp tiền (UC-45 Callback)',
        tags: ['Finance']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['external_id'],
            properties: [
                new OA\Property(property: 'external_id', type: 'string', example: 'TX-123456'),
                new OA\Property(property: 'status', type: 'string', example: 'success'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Xử lý kết quả nạp tiền thành công')]
    public function callback(Request $request): JsonResponse
    {
        $result = $this->walletService->processTopUpCallback($request->all());

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Xử lý kết quả nạp tiền thành công.');
    }
}
