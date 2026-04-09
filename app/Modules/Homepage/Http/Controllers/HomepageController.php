<?php

declare(strict_types=1);

namespace App\Modules\Homepage\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Homepage\Interfaces\HomepageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class HomepageController extends BaseController
{
    protected HomepageServiceInterface $homepageService;

    public function __construct(HomepageServiceInterface $homepageService)
    {
        $this->homepageService = $homepageService;
    }

    #[OA\Get(
        path: '/api/v1/homepage',
        description: 'Trả về dữ liệu trang chủ bao gồm header, dịch vụ, địa chỉ đã lưu, banner, khuyến mãi và gợi ý quán ngon. Hỗ trợ cả Guest và Customer.',
        summary: 'Lấy dữ liệu trang chủ (UC-07)',
        security: [['sanctum' => []]],
        tags: ['Homepage']
    )]
    #[OA\Parameter(
        name: 'lat',
        description: 'Vĩ độ hiện tại của người dùng',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'number', format: 'float')
    )]
    #[OA\Parameter(
        name: 'lng',
        description: 'Kinh độ hiện tại của người dùng',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'number', format: 'float')
    )]
    #[OA\Response(
        response: 200,
        description: 'Thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $lat = $request->query('lat') ? (float) $request->query('lat') : null;
            $lng = $request->query('lng') ? (float) $request->query('lng') : null;

            $result = $this->homepageService->getHomepageData($user, $lat, $lng);

            if (!$result->isSuccess()) {
                return $this->sendError($result->getMessage(), $result->getCode() ?: 400);
            }

            return $this->sendSuccess($result->getData(), 'Lấy dữ liệu trang chủ thành công.');
        } catch (\Throwable $e) {
            return $this->sendError('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }
}
