<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\DTO\ApplyVoucherDTO;
use App\Modules\Ride\DTO\ConfirmBookingDTO;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use App\Modules\Ride\DTO\CancelRideDTO;
use App\Modules\Ride\Http\Requests\ApplyVoucherRequest;
use App\Modules\Ride\Http\Requests\ConfirmBookingRequest;
use App\Modules\Ride\Http\Requests\CreateDraftRideRequest;
use App\Modules\Ride\Http\Requests\CancelRideRequest;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class RideController extends BaseController
{
    public function __construct(
        private readonly RideServiceInterface $rideService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/ride/draft',
        description: 'Tạo một chuyến xe tạm thời (draft) sau khi người dùng nhập địa điểm và chọn loại xe. Hỗ trợ kiểm tra xác thực số điện thoại (A13 flow).',
        summary: 'Tạo chuyến xe tạm thời (UC-08)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['pickup_address', 'pickup_lat', 'pickup_lng', 'destination_address', 'destination_lat', 'destination_lng', 'vehicle_type'],
            properties: [
                new OA\Property(property: 'pickup_address', type: 'string', example: 'Số 1 Đào Duy Anh, Đống Đa, Hà Nội'),
                new OA\Property(property: 'pickup_lat', type: 'number', format: 'float', example: 21.0072),
                new OA\Property(property: 'pickup_lng', type: 'number', format: 'float', example: 105.8428),
                new OA\Property(property: 'destination_address', type: 'string', example: 'Vincom Mega Mall Ocean Park, Gia Lâm, Hà Nội'),
                new OA\Property(property: 'destination_lat', type: 'number', format: 'float', example: 20.9944),
                new OA\Property(property: 'destination_lng', type: 'number', format: 'float', example: 105.9458),
                new OA\Property(
                    property: 'vehicle_type',
                    description: 'Loại phương tiện hỗ trợ. 1: Xe Máy (Bike), 2: Ô Tô 4 Chỗ (Car 4 Seats), 3: Ô Tô 7 Chỗ (Car 7 Seats), 4: Ô Tô 9 Chỗ (Car 9 Seats)',
                    type: 'integer',
                    example: 1
                ),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Chuyến xe tạm thời được tạo thành công')]
    #[OA\Response(response: 403, description: 'Chưa xác thực số điện thoại (A13)')]
    public function createDraft(CreateDraftRideRequest $request): JsonResponse
    {
        // FormRequest đã validate, Controller chỉ map sang DTO và gọi Service
        $result = $this->rideService->createDraft(
            CreateDraftRideDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/ride/{rideId}/vehicles',
        description: 'Lấy danh sách loại xe khả dụng kèm giá ước tính và thời gian chờ dựa trên tuyến đường của chuyến xe nháp.',
        summary: 'Lấy danh sách loại xe (UC-09)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Danh sách xe và giá ước tính')]
    #[OA\Response(response: 404, description: 'Không tìm thấy chuyến xe')]
    public function getVehicleOptions(int $rideId, Request $request): JsonResponse
    {
//        dd($request);
        $result = $this->rideService->getVehicleOptions($rideId, (int) $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/ride/{rideId}/price',
        description: 'Xem chi tiết giá cước ước tính cho chuyến đi bao gồm cấu thành giá, khoảng cách, thời gian và mã giảm giá (nếu có).',
        summary: 'Xem giá ước tính (UC-10)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: 'Chi tiết giá cước',
        content: new OA\JsonContent(ref: '#/components/schemas/PriceEstimateResponse')
    )]
    public function getPriceEstimate(int $rideId, Request $request): JsonResponse
    {
        $result = $this->rideService->getPriceEstimate($rideId, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/ride/{rideId}/voucher',
        description: 'Áp dụng mã giảm giá vào chuyến đi.',
        summary: 'Áp dụng voucher (UC-11)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['voucher_code'],
            properties: [
                new OA\Property(property: 'voucher_code', type: 'string', example: 'DEMO10'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Voucher được áp dụng thành công')]
    #[OA\Response(response: 422, description: 'Voucher không hợp lệ')]
    public function applyVoucher(ApplyVoucherRequest $request, int $rideId): JsonResponse
    {
        $result = $this->rideService->applyVoucher(
            ApplyVoucherDTO::fromRequest($request, $rideId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Voucher đã được áp dụng thành công.');
    }

    #[OA\Delete(
        path: '/api/v1/ride/{rideId}/voucher',
        description: 'Hủy áp dụng voucher đã chọn, giá cước sẽ được khôi phục về giá gốc.',
        summary: 'Xóa voucher (UC-11 A4)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'voucher_code', description: 'Mã voucher', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Voucher đã được xóa, trả về giá gốc')]
    public function removeVoucher(int $rideId, ApplyVoucherRequest $request): JsonResponse
    {
        $result = $this->rideService->removeVoucher($rideId, (int) $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Voucher đã được hủy.');
    }

    #[OA\Post(
        path: '/api/v1/ride/{rideId}/confirm',
        description: 'Xác nhận đặt xe dựa trên giá trị draft. Hệ thống sẽ tính lại giá và so sánh với expected_price từ FE để báo lệch giá nếu có.',
        summary: 'Xác nhận đặt xe (UC-12)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['expected_price'],
            properties: [
                new OA\Property(property: 'expected_price', type: 'number', format: 'float', example: 25000),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking confirmed (Đang tìm tài xế)',
        content: new OA\JsonContent(ref: '#/components/schemas/RideResponse')
    )]
    #[OA\Response(response: 409, description: 'Giá đã thay đổi hoặc voucher không hợp lệ')]
    public function confirmBooking(int $rideId, ConfirmBookingRequest $request): JsonResponse
    {
        $result = $this->rideService->confirmBooking(
            ConfirmBookingDTO::fromRequest($request, $rideId)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Đặt xe thành công. Đang tìm tài xế.');
    }

    #[OA\Post(
        path: '/api/v1/ride/{id}/cancel',
        description: 'Hủy chuyến xe sau khi đã đặt hoặc khi đang tìm tài xế.',
        summary: 'Hủy chuyến xe (UC-15)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'id', description: 'ID của chuyến xe', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Thay đổi kế hoạch'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Hủy chuyến thành công')]
    #[OA\Response(response: 400, description: 'Không thể hủy chuyến')]
    #[OA\Response(response: 404, description: 'Không tìm thấy chuyến xe')]
    public function cancel(int $id, CancelRideRequest $request): JsonResponse
    {
        $result = $this->rideService->cancelRide(
            CancelRideDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Bạn đã hủy chuyến thành công.');
    }
}
