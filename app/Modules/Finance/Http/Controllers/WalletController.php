<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\CancelTopUpDTO;
use App\Modules\Finance\DTO\GetTopUpDetailDTO;
use App\Modules\Finance\DTO\GetTopUpOptionsDTO;
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

    #[OA\Get(
        path: '/api/v1/finance/wallet/top-up/options',
        description: 'Lấy màn hình nạp tiền: số dư ví hiện tại, danh sách phương thức thanh toán Active, mệnh giá gợi ý.',
        summary: 'Màn hình nạp tiền — phương thức & mệnh giá (UC-45)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Response(
        response: 200,
        description: 'Tải màn hình nạp tiền thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'balance', type: 'number', format: 'float', example: 150000),
                new OA\Property(property: 'suggested_amounts', type: 'array', items: new OA\Items(type: 'integer'), example: [50000, 100000, 200000, 500000]),
                new OA\Property(property: 'payment_methods', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'momo'),
                        new OA\Property(property: 'name', type: 'string', example: 'Ví MoMo'),
                        new OA\Property(property: 'type', type: 'string', example: 'e_wallet'),
                        new OA\Property(property: 'type_label', type: 'string', example: 'Ví điện tử'),
                        new OA\Property(property: 'min_amount', type: 'number', example: 10000),
                        new OA\Property(property: 'max_amount', type: 'number', example: 10000000),
                        new OA\Property(property: 'icon_url', type: 'string', nullable: true),
                    ],
                    type: 'object'
                )),
            ]
        )
    )]
    public function getTopUpOptions(Request $request): JsonResponse
    {
        $result = $this->walletService->getTopUpOptions(
            GetTopUpOptionsDTO::fromRequest($request)
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Tải màn hình nạp tiền thành công.');
    }

    #[OA\Post(
        path: '/api/v1/finance/wallet/top-up',
        description: 'Tạo yêu cầu nạp tiền. Hệ thống validate phương thức theo cấu hình Admin và trả về redirect_url (e_wallet/bank_card) hoặc transfer_info (bank_transfer).',
        summary: 'Tạo yêu cầu nạp tiền (UC-45)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['amount', 'payment_method_code'],
            properties: [
                new OA\Property(property: 'amount', type: 'number', example: 100000, description: 'Số tiền nạp (đơn vị VND)'),
                new OA\Property(property: 'payment_method_code', type: 'string', example: 'momo', description: 'Code phương thức từ API /top-up/options'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Khởi tạo giao dịch nạp tiền thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'top_up_id', type: 'string', example: '123456789'),
                new OA\Property(property: 'external_id', type: 'string', example: 'TX-MOM-68461AB2C8A12'),
                new OA\Property(property: 'amount', type: 'number', example: 100000),
                new OA\Property(property: 'payment_method', type: 'string', example: 'momo'),
                new OA\Property(property: 'status', type: 'string', example: 'pending'),
                new OA\Property(property: 'status_label', type: 'string', example: 'Đang xử lý'),
                new OA\Property(property: 'redirect_url', type: 'string', nullable: true, description: 'URL redirect cho e_wallet/bank_card'),
                new OA\Property(
                    property: 'transfer_info',
                    nullable: true,
                    description: 'Thông tin chuyển khoản (bank_transfer)',
                    properties: [
                        new OA\Property(property: 'bank_name', type: 'string', example: 'Vietcombank'),
                        new OA\Property(property: 'account_number', type: 'string', example: '1234567890'),
                        new OA\Property(property: 'account_name', type: 'string', example: 'CONG TY NHM'),
                        new OA\Property(property: 'bank_code', type: 'string', example: 'VCB'),
                        new OA\Property(property: 'qr_url', type: 'string', nullable: true),
                        new OA\Property(property: 'transfer_content', type: 'string', example: 'NAPTIEN TX-BAN-ABC123'),
                        new OA\Property(property: 'amount', type: 'number', example: 100000),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Số tiền không hợp lệ hoặc phương thức không khả dụng')]
    public function initiateTopUp(InitiateTopUpRequest $request): JsonResponse
    {
        $result = $this->walletService->initiateTopUp(
            InitiateTopUpDTO::fromRequest($request)
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Khởi tạo giao dịch nạp tiền thành công.');
    }

    #[OA\Get(
        path: '/api/v1/finance/wallet/top-up/{topUpId}',
        description: 'Xem chi tiết một giao dịch nạp tiền của Driver.',
        summary: 'Chi tiết giao dịch nạp tiền (UC-45)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Parameter(name: 'topUpId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Chi tiết giao dịch nạp tiền',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string'),
                new OA\Property(property: 'amount', type: 'number', example: 100000),
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'status_label', type: 'string', example: 'Thành công'),
                new OA\Property(property: 'payment_method', type: 'string', example: 'momo'),
                new OA\Property(property: 'external_id', type: 'string'),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Không tìm thấy giao dịch')]
    public function getTopUpDetail(Request $request, string $topUpId): JsonResponse
    {
        $result = $this->walletService->getTopUpDetail(
            GetTopUpDetailDTO::fromRequest($request, $topUpId)
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Tải chi tiết giao dịch nạp tiền thành công.');
    }

    #[OA\Delete(
        path: '/api/v1/finance/wallet/top-up/{topUpId}',
        description: 'Driver hủy giao dịch nạp tiền đang ở trạng thái Pending (UC-45 A4).',
        summary: 'Hủy giao dịch nạp tiền (UC-45 A4)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Parameter(name: 'topUpId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Hủy giao dịch thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'top_up_id', type: 'string'),
                new OA\Property(property: 'status', type: 'string', example: 'cancelled'),
                new OA\Property(property: 'message', type: 'string', example: 'Giao dịch nạp tiền đã được hủy.'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Giao dịch không thể hủy (đã xử lý xong)')]
    #[OA\Response(response: 404, description: 'Không tìm thấy giao dịch')]
    public function cancelTopUp(Request $request, string $topUpId): JsonResponse
    {
        $result = $this->walletService->cancelTopUp(
            CancelTopUpDTO::fromRequest($request, $topUpId)
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Hủy giao dịch nạp tiền thành công.');
    }

    #[OA\Post(
        path: '/api/v1/finance/wallet/top-up/callback',
        description: 'Nhận phản hồi từ cổng thanh toán để cập nhật trạng thái nạp tiền. Idempotent — an toàn khi gọi nhiều lần.',
        summary: 'Callback nạp tiền (UC-45)',
        tags: ['Finance']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['external_id'],
            properties: [
                new OA\Property(property: 'external_id', type: 'string', example: 'TX-MOM-68461AB2C8A12'),
                new OA\Property(property: 'status', type: 'string', enum: ['success', 'failed', 'cancelled'], example: 'success'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Xử lý kết quả nạp tiền thành công')]
    #[OA\Response(response: 404, description: 'Không tìm thấy giao dịch')]
    public function callback(Request $request): JsonResponse
    {
        $result = $this->walletService->processTopUpCallback($request->all());
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Xử lý kết quả nạp tiền thành công.');
    }
}
