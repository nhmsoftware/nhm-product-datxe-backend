<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Core\Services\ServiceException;
use App\Modules\User\Http\Resources\ProfileResource;
use App\Modules\User\Interfaces\ProfileServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProfileController extends BaseController
{
    public function __construct(
        private readonly ProfileServiceInterface $profileService
    ) {}

    /**
     * UC-04: View Profile
     *
     * Hiển thị thông tin cá nhân của người dùng đã đăng nhập.
     */
    #[OA\Get(
        path: '/api/v1/user/profile',
        description: 'Cho phép người dùng đã đăng nhập (Customer, Driver hoặc Merchant) xem thông tin tài khoản cá nhân của mình.',
        summary: 'UC-04: Xem thông tin hồ sơ',
        security: [['sanctum' => []]],
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công - Trả về thông tin hồ sơ',
                content: new OA\JsonContent(
                    required: ['success', 'data', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', ref: '#/components/schemas/ProfileResponse'),
                        new OA\Property(property: 'message', type: 'string', example: 'Lấy thông tin hồ sơ thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản bị khóa (A5)',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi tải dữ liệu (A4)',
                content: new OA\JsonContent(
                    required: ['success', 'message'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải thông tin hồ sơ. Vui lòng kiểm tra kết nối và thử lại.')
                    ]
                )
            )
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        try {
            $serviceReturn = $this->profileService->getProfile($request->user());

            return $this->sendSuccess(
                data: (new ProfileResource($serviceReturn->getData()))->toArray($request),
                message: 'Lấy thông tin hồ sơ thành công.'
            );
        } catch (ServiceException $e) {
            return $this->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
