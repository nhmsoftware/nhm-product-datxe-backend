<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\CancelTopUpDTO;
use App\Modules\Finance\DTO\GetTopUpDetailDTO;
use App\Modules\Finance\DTO\GetTopUpOptionsDTO;
use App\Modules\Finance\Interfaces\AdminPaymentMethodServiceInterface;
use App\Modules\Finance\Http\Requests\AdminPaymentMethodRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin / Finance / Payment Methods', description: 'Quản lý phương thức nạp tiền (Admin)')]
final class AdminPaymentMethodController extends BaseController
{
    public function __construct(
        private readonly AdminPaymentMethodServiceInterface $paymentMethodService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/finance/payment-methods',
        description: 'Lấy danh sách tất cả phương thức nạp tiền (kể cả inactive). Admin quản lý.',
        summary: 'Danh sách phương thức nạp tiền (Admin)',
        security: [['sanctum' => []]],
        tags: ['Admin / Finance / Payment Methods'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Danh sách phương thức nạp tiền',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: '1'),
                        new OA\Property(property: 'type', type: 'string', example: 'e_wallet'),
                        new OA\Property(property: 'type_label', type: 'string', example: 'Ví điện tử'),
                        new OA\Property(property: 'code', type: 'string', example: 'momo'),
                        new OA\Property(property: 'name', type: 'string', example: 'Ví MoMo'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'min_amount', type: 'number', example: 10000),
                        new OA\Property(property: 'max_amount', type: 'number', example: 10000000),
                    ],
                    type: 'object'
                )),
            ]
        )
    )]
    public function index(): JsonResponse
    {
        $result = $this->paymentMethodService->index();
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Tải danh sách phương thức nạp tiền thành công.');
    }

    #[OA\Post(
        path: '/api/v1/admin/finance/payment-methods',
        description: 'Tạo phương thức nạp tiền mới. Loại: e_wallet | bank_card | bank_transfer.',
        summary: 'Tạo phương thức nạp tiền (Admin)',
        security: [['sanctum' => []]],
        tags: ['Admin / Finance / Payment Methods'],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['type', 'code', 'name'],
            properties: [
                new OA\Property(property: 'type', type: 'string', enum: ['e_wallet', 'bank_card', 'bank_transfer'], example: 'e_wallet'),
                new OA\Property(property: 'code', type: 'string', example: 'momo'),
                new OA\Property(property: 'name', type: 'string', example: 'Ví MoMo'),
                new OA\Property(property: 'is_active', type: 'boolean', example: false),
                new OA\Property(property: 'min_amount', type: 'number', example: 10000),
                new OA\Property(property: 'max_amount', type: 'number', example: 10000000),
                new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                new OA\Property(
                    property: 'transfer_info',
                    description: 'Bắt buộc nếu type = bank_transfer',
                    properties: [
                        new OA\Property(property: 'bank_name', type: 'string', example: 'Vietcombank'),
                        new OA\Property(property: 'account_number', type: 'string', example: '1234567890'),
                        new OA\Property(property: 'account_name', type: 'string', example: 'CONG TY NHM'),
                        new OA\Property(property: 'bank_code', type: 'string', example: 'VCB'),
                        new OA\Property(property: 'qr_url', type: 'string', nullable: true),
                    ],
                    type: 'object',
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Tạo phương thức nạp tiền thành công')]
    #[OA\Response(response: 400, description: 'Dữ liệu không hợp lệ')]
    public function store(AdminPaymentMethodRequest $request): JsonResponse
    {
        $result = $this->paymentMethodService->store(
            $request->validated(),
            (string) $request->user()->id
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), 'Tạo phương thức nạp tiền thành công.');
    }

    #[OA\Put(
        path: '/api/v1/admin/finance/payment-methods/{id}',
        description: 'Cập nhật thông tin phương thức nạp tiền.',
        summary: 'Cập nhật phương thức nạp tiền (Admin) (UC-132)',
        security: [['sanctum' => []]],
        tags: ['Admin / Finance / Payment Methods'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Ví MoMo'),
                new OA\Property(property: 'min_amount', type: 'number', example: 10000),
                new OA\Property(property: 'max_amount', type: 'number', example: 5000000),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                new OA\Property(property: 'confirm', type: 'boolean', example: false, description: 'Xác nhận vô hiệu hóa kể cả khi có giao dịch pending'),
                new OA\Property(property: 'transfer_info', type: 'object', nullable: true),
                new OA\Property(
                    property: 'metadata',
                    properties: [
                        // MoMo
                        new OA\Property(property: 'merchant_id', type: 'string', example: 'MOMO123', description: 'Dành cho MoMo'),
                        new OA\Property(property: 'partner_code', type: 'string', example: 'MOMO123', description: 'Dành cho MoMo'),
                        new OA\Property(property: 'access_key', type: 'string', example: 'acc_abc', description: 'Dành cho MoMo'),
                        new OA\Property(property: 'secret_key', type: 'string', example: 'sec_xyz', description: 'Dành cho MoMo'),
                        // ZaloPay
                        new OA\Property(property: 'app_id', type: 'string', example: '2553', description: 'Dành cho ZaloPay'),
                        new OA\Property(property: 'key_1', type: 'string', example: 'key1_abc', description: 'Dành cho ZaloPay'),
                        new OA\Property(property: 'key_2', type: 'string', example: 'key2_xyz', description: 'Dành cho ZaloPay'),
                        // payOS
                        new OA\Property(property: 'client_id', type: 'string', example: 'client_abc', description: 'Dành cho payOS'),
                        new OA\Property(property: 'api_key', type: 'string', example: 'key_abc', description: 'Dành cho payOS'),
                        new OA\Property(property: 'checksum_key', type: 'string', example: 'chk_xyz', description: 'Dành cho payOS'),
                        // Common
                        new OA\Property(property: 'endpoint', type: 'string', example: 'https://payment.momo.vn'),
                        new OA\Property(property: 'webhook_url', type: 'string', example: 'https://api.nhm.com/callback'),
                        new OA\Property(property: 'transaction_fee', type: 'number', example: 1.5),
                        new OA\Property(property: 'internal_note', type: 'string', example: 'Internal notes'),
                        new OA\Property(property: 'require_otp', type: 'boolean', example: true),
                    ],
                    type: 'object',
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    #[OA\Response(response: 400, description: 'Dữ liệu không hợp lệ')]
    #[OA\Response(response: 404, description: 'Không tìm thấy phương thức')]
    public function update(AdminPaymentMethodRequest $request, string $id): JsonResponse
    {
        $result = $this->paymentMethodService->update(
            $id,
            $request->validated(),
            (string) $request->user()->id,
            (bool) $request->input('confirm', false)
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage() ?: 'Cập nhật phương thức nạp tiền thành công.');
    }

    #[OA\Patch(
        path: '/api/v1/admin/finance/payment-methods/{id}/toggle',
        description: 'Bật/tắt phương thức nạp tiền. Driver chỉ thấy phương thức đang Active.',
        summary: 'Bật/tắt phương thức nạp tiền (Admin) (UC-132)',
        security: [['sanctum' => []]],
        tags: ['Admin / Finance / Payment Methods'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'confirm', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', default: false), description: 'Xác nhận vô hiệu hóa kể cả khi có giao dịch pending')]
    #[OA\Response(
        response: 200,
        description: 'Toggle thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string'),
                new OA\Property(property: 'is_active', type: 'boolean'),
                new OA\Property(property: 'message', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Không tìm thấy phương thức')]
    public function toggle(Request $request, string $id): JsonResponse
    {
        $result = $this->paymentMethodService->toggle(
            $id, 
            (string) $request->user()->id,
            (bool) $request->input('confirm', false)
        );
        if ($result->isError()) return $this->sendError($result->getMessage(), $result->getCode());
        return $this->sendSuccess($result->getData(), $result->getMessage() ?: ($result->getData()['message'] ?? ''));
    }
}
