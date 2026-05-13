<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\UpdateCreditWalletConfigDTO;
use App\Modules\Finance\Interfaces\CreditWalletConfigServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AdminCreditWalletConfigController extends BaseController
{
    public function __construct(
        private readonly CreditWalletConfigServiceInterface $configService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/finance/credit-wallet-config',
        summary: 'Lấy cấu hình Credit Wallet hiện tại (UC-117)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Finance Config'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'min_balance', type: 'number', example: 50000),
                        new OA\Property(property: 'auto_lock', type: 'boolean', example: true),
                        new OA\Property(property: 'commission_rule', type: 'string', example: 'Default rule description'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ]
                )
            )
        ]
    )]
    public function show(): JsonResponse
    {
        $result = $this->configService->getConfig();
        return $this->sendResponse($result->getData());
    }

    #[OA\Post(
        path: '/api/v1/admin/finance/credit-wallet-config',
        summary: 'Cập nhật cấu hình Credit Wallet (UC-117)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Finance Config'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['min_balance'],
                properties: [
                    new OA\Property(property: 'min_balance', type: 'number', example: 100000),
                    new OA\Property(property: 'auto_lock', type: 'boolean', example: true),
                    new OA\Property(property: 'commission_rule', type: 'string', example: 'New deduction rule'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cấu hình Credit Wallet thành công.'),
            new OA\Response(response: 400, description: 'Thông tin cấu hình không hợp lệ.'),
            new OA\Response(response: 500, description: 'Không thể cập nhật Credit Wallet Configuration.'),
        ]
    )]
    public function update(Request $request): JsonResponse
    {
        // Simple manual validation for A1/A2
        if (!$request->has('min_balance') || !is_numeric($request->input('min_balance'))) {
            return $this->sendError('Thông tin cấu hình không hợp lệ.', 400);
        }

        $dto = UpdateCreditWalletConfigDTO::fromRequest($request);
        $adminId = (int) auth()->id();

        $result = $this->configService->updateConfig($dto, $adminId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), 400);
        }

        return $this->sendSuccess($result->getData(), 'Cấu hình Credit Wallet thành công.');
    }
}
