<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Finance\DTO\RegisterSubscriptionDTO;
use App\Modules\Finance\Http\Requests\RegisterSubscriptionRequest;
use App\Modules\Finance\Interfaces\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class SubscriptionController extends BaseController
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService
    ) {}

    #[OA\Get(
        path: '/api/v1/finance/subscriptions/packages',
        description: 'Lấy danh sách các gói thành viên đang hoạt động dành cho tài xế.',
        summary: 'Danh sách gói thành viên (UC-46)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\Response(
        response: 200,
        description: 'Tải danh sách gói thành viên thành công',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SubscriptionPackageResponse')
        )
    )]
    public function packages(): JsonResponse
    {
        $result = $this->subscriptionService->getAvailablePackages();

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tải danh sách gói thành viên thành công.');
    }

    #[OA\Post(
        path: '/api/v1/finance/subscriptions/register',
        description: 'Đăng ký một gói thành viên mới bằng cách sử dụng số dư ví tín dụng. package_id phải lấy từ danh sách gói thành viên hợp lệ từ API /api/v1/finance/subscriptions/packages.',
        summary: 'Đăng ký gói thành viên (UC-46)',
        security: [['sanctum' => []]],
        tags: ['Finance']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['package_id'],
            properties: [
                new OA\Property(property: 'package_id', type: 'string', example: '1', description: 'ID của gói thành viên lấy từ API danh sách gói'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Đăng ký gói thành viên thành công')]
    #[OA\Response(response: 400, description: 'Số dư không đủ hoặc đã có gói hoạt động')]
    #[OA\Response(response: 404, description: 'Gói không tồn tại hoặc không còn hiệu lực')]
    public function register(RegisterSubscriptionRequest $request): JsonResponse
    {
        $result = $this->subscriptionService->registerSubscription(
            RegisterSubscriptionDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Đăng ký gói thành viên thành công.');
    }
}
