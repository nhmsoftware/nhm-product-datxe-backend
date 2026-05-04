<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\DTO\ApplyVoucherDTO;
use App\Modules\Ride\DTO\ConfirmBookingDTO;
use App\Modules\Ride\DTO\CreateIntercityRideDTO;
use App\Modules\Ride\DTO\FilterScheduledRideDTO;
use App\Modules\Ride\DTO\RespondRideCancellationDTO;
use App\Modules\Ride\DTO\CreateAirportRideDTO;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use App\Modules\Ride\DTO\CancelRideDTO;
use App\Modules\Ride\Http\Requests\ApplyVoucherRequest;
use App\Modules\Ride\Http\Requests\ConfirmBookingRequest;
use App\Modules\Ride\Http\Requests\CreateIntercityRideRequest;
use App\Modules\Ride\Http\Requests\GetScheduledRideListRequest;
use App\Modules\Ride\Http\Requests\RespondRideCancellationRequest;
use App\Modules\Ride\Http\Requests\CreateAirportRideRequest;
use App\Modules\Ride\Http\Requests\CreateDraftRideRequest;
use App\Modules\Ride\Http\Requests\CancelRideRequest;
use App\Modules\Ride\Http\Requests\GetVehicleOptionsRequest;
use App\Modules\Ride\Http\Requests\GetPriceEstimateRequest;
use App\Modules\Ride\Http\Requests\RequestRideCancellationRequest;
use App\Modules\Ride\DTO\RequestRideCancellationDTO;
use App\Modules\Ride\DTO\AcceptRideTrackingDTO;
use App\Modules\Ride\DTO\DriverCancelRideDTO;
use App\Modules\Ride\DTO\MarkDriverArrivedDTO;
use App\Modules\Ride\DTO\ShowRideTrackingDTO;
use App\Modules\Ride\DTO\UpdateDriverLocationDTO;
use App\Modules\Ride\Http\Requests\AcceptRideTrackingRequest;
use App\Modules\Ride\Http\Requests\DriverCancelRideRequest;
use App\Modules\Ride\Http\Requests\MarkDriverArrivedRequest;
use App\Modules\Ride\Http\Requests\UpdateDriverLocationRequest;
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
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Danh sách xe và giá ước tính')]
    #[OA\Response(response: 404, description: 'Không tìm thấy chuyến xe')]
    public function getVehicleOptions(GetVehicleOptionsRequest $request): JsonResponse
    {
        $result = $this->rideService->getVehicleOptions(
            (string) $request->route('rideId'),
            (string) $request->user()->id
        );

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
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Chi tiết giá cước',
        content: new OA\JsonContent(ref: '#/components/schemas/PriceEstimateResponse')
    )]
    public function getPriceEstimate(GetPriceEstimateRequest $request): JsonResponse
    {
        $result = $this->rideService->getPriceEstimate(
            (string) $request->route('rideId'),
            (string) $request->user()->id
        );

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
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
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
    public function applyVoucher(ApplyVoucherRequest $request): JsonResponse
    {
        $result = $this->rideService->applyVoucher(
            ApplyVoucherDTO::fromRequest($request)
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
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'voucher_code', description: 'Mã voucher', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Voucher đã được xóa, trả về giá gốc')]
    public function removeVoucher(GetPriceEstimateRequest $request): JsonResponse
    {
        $result = $this->rideService->removeVoucher(
            (string) $request->route('rideId'),
            (string) $request->user()->id
        );

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
    #[OA\Parameter(name: 'rideId', description: 'ID của ride draft', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
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
    public function confirmBooking(ConfirmBookingRequest $request): JsonResponse
    {
        $result = $this->rideService->confirmBooking(
            ConfirmBookingDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Đặt xe thành công. Đang tìm tài xế.');
    }

    #[OA\Post(
        path: '/api/v1/ride/{rideId}/cancel',
        description: 'Hủy chuyến xe sau khi đã đặt hoặc khi đang tìm tài xế.',
        summary: 'Hủy chuyến xe (UC-15)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của chuyến xe', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
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
    public function cancel(CancelRideRequest $request): JsonResponse
    {
        $result = $this->rideService->cancelRide(
            CancelRideDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Bạn đã hủy chuyến thành công.');
    }

    #[OA\Post(
        path: '/api/v1/ride/{rideId}/cancel-request',
        description: 'Khách hàng gửi yêu cầu hủy chuyến đi. Nếu đã có tài xế, sẽ cần tài xế xác nhận.',
        summary: 'Yêu cầu hủy chuyến (UC-28)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của chuyến xe', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Thay đổi lộ trình'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Yêu cầu đã được gửi hoặc hủy thành công')]
    public function requestCancellation(RequestRideCancellationRequest $request): JsonResponse
    {
        $result = $this->rideService->requestCancellation(
            RequestRideCancellationDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/ride/{rideId}/tracking',
        description: 'Xem thông tin theo dõi chuyến xe realtime cho khách hàng.',
        summary: 'Xem theo dõi chuyến xe (UC-13)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của chuyến xe', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Thông tin theo dõi chuyến xe')]
    public function showTracking(GetPriceEstimateRequest $request): JsonResponse
    {
        $result = $this->rideService->showTracking(
            ShowRideTrackingDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }



    #[OA\Post(
        path: '/api/v1/ride/intercity',
        summary: 'Đặt xe đi tỉnh (UC-26)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'pickup_address', type: 'string', example: 'Hà Nội'),
                new OA\Property(property: 'pickup_lat', type: 'number', example: 21.0285),
                new OA\Property(property: 'pickup_lng', type: 'number', example: 105.8542),
                new OA\Property(property: 'destination_address', type: 'string', example: 'Hải Phòng'),
                new OA\Property(property: 'destination_lat', type: 'number', example: 20.8449),
                new OA\Property(property: 'destination_lng', type: 'number', example: 106.6881),
                new OA\Property(property: 'travel_date', type: 'string', example: '2024-05-01'),
                new OA\Property(property: 'travel_time', type: 'string', example: '08:00'),
                new OA\Property(property: 'vehicle_type', type: 'integer', example: 2, description: '2: CAR_4_SEATS, 3: CAR_7_SEATS, 4: CAR_9_SEATS, 5: CAR_SHARED'),
                new OA\Property(property: 'voucher_code', type: 'string', example: 'DISCOUNT10'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Đặt xe thành công')]
    public function createIntercity(CreateIntercityRideRequest $request): JsonResponse
    {
        $result = $this->rideService->createIntercity(
            CreateIntercityRideDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/ride/airport',
        summary: 'Đặt xe sân bay (UC-27)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'pickup_address', type: 'string', example: 'Hà Nội'),
                new OA\Property(property: 'pickup_lat', type: 'number', example: 21.0285),
                new OA\Property(property: 'pickup_lng', type: 'number', example: 105.8542),
                new OA\Property(property: 'destination_address', type: 'string', example: 'Sân bay Nội Bài'),
                new OA\Property(property: 'destination_lat', type: 'number', example: 21.2129),
                new OA\Property(property: 'destination_lng', type: 'number', example: 105.8042),
                new OA\Property(property: 'travel_date', type: 'string', example: '2024-05-01'),
                new OA\Property(property: 'travel_time', type: 'string', example: '08:00'),
                new OA\Property(property: 'vehicle_type', type: 'integer', example: 2),
                new OA\Property(property: 'airport_id', type: 'integer', example: 1),
                new OA\Property(property: 'airport_direction', type: 'integer', example: 1, description: '1: To Airport, 2: From Airport'),
                new OA\Property(property: 'voucher_code', type: 'string', example: 'DISCOUNT10'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Đặt xe thành công')]
    public function createAirport(CreateAirportRideRequest $request): JsonResponse
    {
        $result = $this->rideService->createAirport(
            CreateAirportRideDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/ride/{rideId}/cancel-response',
        description: 'Tài xế phản hồi yêu cầu hủy chuyến của khách hàng (Đồng ý/Từ chối).',
        summary: 'Phản hồi yêu cầu hủy (UC-28 Driver Response)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của chuyến xe', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'is_approved', type: 'boolean', example: true, description: 'true: Đồng ý hủy, false: Từ chối hủy'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Phản hồi thành công')]
    public function respondToCancellation(RespondRideCancellationRequest $request): JsonResponse
    {
        $result = $this->rideService->respondToCancellation(
            RespondRideCancellationDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/ride/{rideId}',
        description: 'Xem chi tiết chuyến xe (UC-29). Trả về thông tin đầy đủ kèm tài xế nếu có.',
        summary: 'Xem chi tiết chuyến xe (UC-29)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của chuyến xe', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Lấy thông tin thành công')]
    public function show(string $rideId, Request $request): JsonResponse
    {
        $result = $this->rideService->getRideDetail($rideId, (string) $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/driver/scheduled-rides',
        description: 'Tài xế xem danh sách các chuyến xe đặt trước phù hợp với loại xe đã đăng ký (UC-47).',
        summary: 'Danh sách chuyến xe đặt trước cho tài xế (UC-47)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'travel_date', description: 'Ngày đi (Y-m-d)', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'travel_time', description: 'Giờ đi (H:i)', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'ride_type', description: 'Loại chuyến (1: Nội thành, 2: Đi tỉnh, 3: Sân bay)', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'min_price', description: 'Giá tối thiểu', in: 'query', required: false, schema: new OA\Schema(type: 'number'))]
    #[OA\Parameter(name: 'max_price', description: 'Giá tối đa', in: 'query', required: false, schema: new OA\Schema(type: 'number'))]
    #[OA\Response(response: 200, description: 'Lấy danh sách thành công')]
    public function getAvailableScheduledRides(GetScheduledRideListRequest $request): JsonResponse
    {
        $result = $this->rideService->getAvailableScheduledRides(
            FilterScheduledRideDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/driver/scheduled-rides/{rideId}',
        description: 'Tài xế xem chi tiết một chuyến xe đặt trước (UC-48).',
        summary: 'Chi tiết chuyến xe đặt trước cho tài xế (UC-48)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của chuyến xe', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Lấy chi tiết thành công')]
    #[OA\Response(response: 400, description: 'Chuyến xe không còn khả dụng')]
    #[OA\Response(response: 403, description: 'Không đủ điều kiện xem')]
    #[OA\Response(response: 404, description: 'Không tìm thấy chuyến xe')]
    public function getScheduledRideDetail(string $rideId, Request $request): JsonResponse
    {
        $result = $this->rideService->getScheduledRideDetail($rideId, (string) $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/driver/scheduled-rides/{rideId}/accept',
        description: 'Tài xế nhận một chuyến xe đặt trước (UC-49).',
        summary: 'Nhận chuyến xe đặt trước (UC-49)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của chuyến xe', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Nhận chuyến thành công')]
    #[OA\Response(response: 400, description: 'Không thể nhận chuyến')]
    public function acceptScheduledRide(string $rideId, Request $request): JsonResponse
    {
        $result = $this->rideService->acceptScheduledRide($rideId, (string) $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/driver/scheduled-rides/{rideId}/cancel',
        description: 'Tài xế hủy chuyến xe đặt trước đã nhận (UC-50). Chỉ được hủy trong thời gian quy định.',
        summary: 'Hủy chuyến xe đặt trước (UC-50 Driver)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'rideId', description: 'ID của chuyến xe', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Hỏng xe đột xuất'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Hủy chuyến thành công')]
    #[OA\Response(response: 400, description: 'Đã quá thời gian cho phép tự hủy')]
    public function driverCancelScheduledRide(DriverCancelRideRequest $request): JsonResponse
    {
        $result = $this->rideService->driverCancelScheduledRide(
            DriverCancelRideDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/driver/managed-rides',
        description: 'Tài xế xem danh sách các chuyến xe đã nhận và đang quản lý (UC-51).',
        summary: 'Danh sách chuyến xe đang quản lý của tài xế (UC-51)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Response(response: 200, description: 'Lấy danh sách thành công')]
    public function getDriverManagedRides(Request $request): JsonResponse
    {
        $result = $this->rideService->getDriverManagedRides((string) $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
    #[OA\Get(
        path: '/api/v1/ride/airports',
        description: 'Lấy danh sách các sân bay được hỗ trợ để đặt xe (UC-27).',
        summary: 'Danh sách sân bay hỗ trợ (UC-27)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Response(response: 200, description: 'Lấy danh sách thành công')]
    public function listAirports(): JsonResponse
    {
        $result = $this->rideService->getAirports();

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy danh sách sân bay thành công.');
    }
}
