<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Ride\DTO\ConfirmBookingDTO;
use App\Modules\Ride\DTO\CreateIntercityRideDTO;
use App\Modules\Ride\DTO\EstimateRideOptionsDTO;
use App\Modules\Ride\DTO\FilterScheduledRideDTO;
use App\Modules\Ride\DTO\CreateAirportRideDTO;
use App\Modules\Ride\DTO\CancelRideDTO;
use App\Modules\Ride\DTO\CreateDeliveryOrderDTO;
use App\Modules\Ride\DTO\CapturePickupProofDTO;
use App\Modules\Ride\DTO\CaptureDeliveryProofDTO;
use App\Modules\Driver\DTO\RespondRideCancellationDTO;
use App\Modules\Ride\Http\Requests\CreateDeliveryOrderRequest;
use App\Modules\Ride\Http\Requests\CapturePickupProofRequest;
use App\Modules\Ride\Http\Requests\CaptureDeliveryProofRequest;
use App\Modules\Ride\Http\Requests\ConfirmBookingRequest;
use App\Modules\Ride\Http\Requests\CreateIntercityRideRequest;
use App\Modules\Ride\Http\Requests\EstimateRideOptionsRequest;
use App\Modules\Ride\Http\Requests\GetScheduledRideListRequest;
use App\Modules\Ride\Http\Requests\RespondRideCancellationRequest;
use App\Modules\Ride\Http\Requests\CreateAirportRideRequest;
use App\Modules\Ride\Http\Requests\CancelRideRequest;
use App\Modules\Ride\Http\Requests\GetPriceEstimateRequest;
use App\Modules\Ride\Http\Requests\RequestRideCancellationRequest;
use App\Modules\Ride\DTO\RequestRideCancellationDTO;
use App\Modules\Ride\DTO\DriverCancelRideDTO;
use App\Modules\Ride\DTO\GetDriverRidesFilterDTO;
use App\Modules\Ride\Http\Requests\GetDriverRidesRequest;
use App\Modules\Ride\DTO\ShowRideTrackingDTO;
use App\Modules\Ride\Http\Requests\DriverCancelRideRequest;
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
        path: '/api/v1/ride/vehicle-options',
        description: 'Lấy danh sách loại xe khả dụng kèm giá ước tính và thời gian chờ dựa trên tọa độ điểm đón và điểm đến. API này không tạo draft ride.',
        summary: 'Lấy danh sách loại xe kèm giá ước tính (UC-09)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['pickup_lat', 'pickup_lng', 'destination_lat', 'destination_lng'],
            properties: [
                new OA\Property(property: 'pickup_lat', type: 'number', format: 'float', example: 21.0072),
                new OA\Property(property: 'pickup_lng', type: 'number', format: 'float', example: 105.8428),
                new OA\Property(property: 'destination_lat', type: 'number', format: 'float', example: 20.9944),
                new OA\Property(property: 'destination_lng', type: 'number', format: 'float', example: 105.9458),
                new OA\Property(property: 'service_type', type: 'string', example: 'intercity', description: 'Loại dịch vụ (city, intercity, airport, delivery). Mặc định city.', nullable: true),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Danh sách xe và giá ước tính',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'distance_km', type: 'number', example: 12.4),
                new OA\Property(property: 'duration_minutes', type: 'integer', example: 24),
                new OA\Property(
                    property: 'vehicle_options',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'vehicle_type', type: 'integer', example: 2),
                            new OA\Property(property: 'name', type: 'string', example: 'Ô Tô 4 Chỗ'),
                            new OA\Property(property: 'estimated_fare', type: 'number', example: 85000),
                            new OA\Property(property: 'estimated_wait_time', type: 'string', example: '3-7 phút'),
                            new OA\Property(property: 'is_available', type: 'boolean', example: true),
                        ]
                    )
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Dữ liệu không hợp lệ')]
    public function estimateRideOptions(EstimateRideOptionsRequest $request): JsonResponse
    {
        $result = $this->rideService->estimateRideOptions(
            EstimateRideOptionsDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy danh sách loại xe thành công.');
    }

    #[OA\Post(
        path: '/api/v1/ride/confirm',
        description: 'Xác nhận đặt xe trực tiếp bằng cách truyền thông tin chuyến đi. Hệ thống tính lại giá theo loại xe đã chọn và so sánh với expected_price từ FE.',
        summary: 'Xác nhận đặt xe trực tiếp (UC-12)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['pickup_address', 'pickup_lat', 'pickup_lng', 'destination_address', 'destination_lat', 'destination_lng', 'vehicle_type', 'expected_price'],
            properties: [
                new OA\Property(property: 'pickup_address', type: 'string', example: 'Số 1 Đào Duy Anh, Đống Đa, Hà Nội'),
                new OA\Property(property: 'pickup_lat', type: 'number', format: 'float', example: 21.0072),
                new OA\Property(property: 'pickup_lng', type: 'number', format: 'float', example: 105.8428),
                new OA\Property(property: 'destination_address', type: 'string', example: 'Vincom Mega Mall Ocean Park, Gia Lâm, Hà Nội'),
                new OA\Property(property: 'destination_lat', type: 'number', format: 'float', example: 20.9944),
                new OA\Property(property: 'destination_lng', type: 'number', format: 'float', example: 105.9458),
                new OA\Property(property: 'vehicle_type', type: 'integer', example: 2, description: '1: Xe Máy, 2: 4 chỗ, 3: 7 chỗ, 4: 9 chỗ'),
                new OA\Property(property: 'expected_price', type: 'number', format: 'float', example: 45000, description: 'Giá kỳ vọng lấy từ tính toán để kiểm tra chênh lệch'),
                new OA\Property(property: 'voucher_code', type: 'string', example: 'DEMO10', description: 'Mã giảm giá (tùy chọn)'),
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
        description: 'Lấy danh sách các sân bay được hỗ trợ để đặt xe (UC-27). Nếu truyền vào tọa độ vị trí của khách hàng (lat và lng), danh sách trả về sẽ được tự động sắp xếp theo thứ tự khoảng cách từ gần nhất đến xa nhất.',
        summary: 'Danh sách sân bay hỗ trợ (UC-27)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'lat', description: 'Vĩ độ (Latitude) hiện tại của khách hàng', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float'))]
    #[OA\Parameter(name: 'lng', description: 'Kinh độ (Longitude) hiện tại của khách hàng', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float'))]
    #[OA\Response(response: 200, description: 'Lấy danh sách thành công')]
    public function listAirports(\App\Modules\Ride\Http\Requests\GetAirportsRequest $request): JsonResponse
    {
        $result = $this->rideService->getAirports(\App\Modules\Ride\DTO\GetAirportsDTO::fromRequest($request));

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy danh sách sân bay thành công.');
    }

    #[OA\Post(
        path: '/api/v1/ride/delivery',
        summary: 'Tạo đơn giao hàng (UC-25)',
        description: 'Customer tạo đơn giao hàng từ điểm lấy đến điểm giao. Hệ thống tính giá cước và tìm tài xế gướn đơn.',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: [
                'pickup_address', 'pickup_lat', 'pickup_lng',
                'destination_address', 'destination_lat', 'destination_lng',
                'vehicle_type',
                'sender_name', 'sender_phone',
                'receiver_name', 'receiver_phone',
                'goods_type', 'goods_weight',
            ],
            properties: [
                new OA\Property(property: 'pickup_address',      type: 'string',  example: 'Số 10 Trần Hưng Đạo, Hà Nội'),
                new OA\Property(property: 'pickup_lat',          type: 'number',  format: 'float', example: 21.0285),
                new OA\Property(property: 'pickup_lng',          type: 'number',  format: 'float', example: 105.8542),
                new OA\Property(property: 'destination_address', type: 'string',  example: 'Số 5 Cầu Giấy, Hà Nội'),
                new OA\Property(property: 'destination_lat',     type: 'number',  format: 'float', example: 21.0334),
                new OA\Property(property: 'destination_lng',     type: 'number',  format: 'float', example: 105.7833),
                new OA\Property(
                    property: 'vehicle_type',
                    type: 'integer',
                    example: 1,
                    description: '1 = Xe Máy, 2 = Ô Tô 4 Chỗ'
                ),
                new OA\Property(property: 'sender_name',   type: 'string', example: 'Nguyễn Văn A'),
                new OA\Property(property: 'sender_phone',  type: 'string', example: '0901234567'),
                new OA\Property(property: 'receiver_name', type: 'string', example: 'Trần Thị B'),
                new OA\Property(property: 'receiver_phone',type: 'string', example: '0987654321'),
                new OA\Property(property: 'goods_type',   type: 'string', example: 'Quần áo', description: 'Loại hàng hóa'),
                new OA\Property(property: 'goods_weight', type: 'number', example: 2.5,         description: 'Cân nặng (kg), tối đa 50kg'),
                new OA\Property(property: 'goods_note',   type: 'string', example: 'Dễ vỡ, xếp nhẹ tay', nullable: true),
                new OA\Property(property: 'is_fragile',   type: 'boolean', example: false),
                new OA\Property(property: 'voucher_code', type: 'string', example: 'GIAO10', nullable: true),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Đơn giao hàng được tạo thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ride_id',        type: 'string',  example: '163891324676074472'),
                new OA\Property(property: 'ride_type',      type: 'string',  example: 'delivery'),
                new OA\Property(property: 'total_price',    type: 'number',  example: 45000),
                new OA\Property(property: 'distance_km',    type: 'number',  example: 3.5),
                new OA\Property(property: 'duration_min',   type: 'integer', example: 12),
                new OA\Property(property: 'status',         type: 'string',  example: 'pending'),
                new OA\Property(property: 'status_label',   type: 'string',  example: 'Đang tìm tài xế giao hàng.'),
                new OA\Property(property: 'receiver_name',  type: 'string',  example: 'Trần Thị B'),
                new OA\Property(property: 'receiver_phone', type: 'string',  example: '0987654321'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Chưa đăng nhập')]
    #[OA\Response(response: 422, description: 'Dữ liệu không hợp lệ (A2-A6)')]
    public function createDelivery(CreateDeliveryOrderRequest $request): JsonResponse
    {
        $result = $this->rideService->createDeliveryOrder(
            CreateDeliveryOrderDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Đơn giao hàng được tạo thành công. Đang tìm tài xế.');
    }

    // =========================================================
    // UC-37: Capture Pickup Proof
    // =========================================================

    #[OA\Post(
        path: '/api/v1/driver/ride/{rideId}/pickup-proof',
        summary: 'Chụp/tải ảnh xác nhận đã lấy hàng (UC-37)',
        description: 'Driver gửi ảnh xác nhận đã lấy hàng thành công trước khi bắt đầu giao. Hỗ trợ 2 luồng: (1) Normal: gửi photo + GPS. (2) A3/A6: không chụp được → chọn skip_reason + note.',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(
        name: 'rideId',
        description: 'ID của chuyến xe',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: 'photo',
                        description: 'Ảnh xác nhận lấy hàng (JPG/PNG/WEBP, tối đa 10MB). Bỏ qua nếu A3/A6.',
                        type: 'string',
                        format: 'binary'
                    ),
                    new OA\Property(
                        property: 'captured_lat',
                        description: 'Vĩ độ GPS tại thời điểm chụp',
                        type: 'number',
                        format: 'float',
                        example: 10.762622
                    ),
                    new OA\Property(
                        property: 'captured_lng',
                        description: 'Kinh độ GPS tại thời điểm chụp',
                        type: 'number',
                        format: 'float',
                        example: 106.660172
                    ),
                    new OA\Property(
                        property: 'skip_reason',
                        description: 'A3/A6: Lý do không chụp được ảnh. Bắt buộc khi không có photo.',
                        type: 'string',
                        enum: ['merchant_refused', 'device_error', 'other'],
                        example: 'merchant_refused'
                    ),
                    new OA\Property(
                        property: 'note',
                        description: 'A3/A6: Ghi chú thêm. Bắt buộc khi không có photo.',
                        type: 'string',
                        example: 'Merchant từ chối chụp ảnh vì lý do riêng tư.'
                    ),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Xác nhận lấy hàng thành công. Trạng thái chuyến xe → PICKED_UP.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ride_id',      type: 'string',  example: '163891324676074472'),
                new OA\Property(property: 'status',       type: 'integer', example: 7),
                new OA\Property(property: 'status_label', type: 'string',  example: 'Đã lấy hàng'),
                new OA\Property(property: 'photo_url',    type: 'string',  nullable: true, example: 'https://cdn.example.com/pickup-proofs/163891.../photo.jpg'),
                new OA\Property(property: 'captured_at',  type: 'string',  example: '2026-05-07T19:00:00+07:00'),
                new OA\Property(property: 'is_skipped',   type: 'boolean', example: false),
                new OA\Property(property: 'skip_reason',  type: 'string',  nullable: true, example: null),
                new OA\Property(property: 'message',      type: 'string',  example: 'Ảnh xác nhận lấy hàng đã được lưu thành công. Bạn có thể bắt đầu giao hàng.'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Không tìm thấy chuyến xe')]
    #[OA\Response(response: 422, description: 'Điều kiện tiên quyết chưa đáp ứng hoặc ảnh không hợp lệ (A2/A3/A6)')]
    #[OA\Response(response: 500, description: 'Lỗi tải ảnh lên storage (A4)')]
    public function capturePickupProof(CapturePickupProofRequest $request): JsonResponse
    {
        $result = $this->rideService->capturePickupProof(
            CapturePickupProofDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getData()['message'] ?? 'Xác nhận lấy hàng thành công.');
    }

    // =========================================================
    // UC-38: Capture Delivery Proof
    // =========================================================

    #[OA\Post(
        path: '/api/v1/driver/ride/{rideId}/delivery-proof',
        summary: 'Chụp/tải ảnh xác nhận đã giao hàng (UC-38)',
        description: 'Driver gửi ảnh xác nhận đã giao hàng thành công để hoàn tất đơn hàng. Hỗ trợ (1) Normal flow: photo + GPS. (2) A3 flow: không chụp được → chọn skip_reason + note. Yêu cầu GPS khớp với điểm đích (A6).',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(
        name: 'rideId',
        description: 'ID của chuyến xe',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: 'photo',
                        description: 'Ảnh xác nhận giao hàng (JPG/PNG/WEBP, tối đa 10MB).',
                        type: 'string',
                        format: 'binary'
                    ),
                    new OA\Property(
                        property: 'captured_lat',
                        description: 'Vĩ độ GPS tại thời điểm chụp',
                        type: 'number',
                        format: 'float'
                    ),
                    new OA\Property(
                        property: 'captured_lng',
                        description: 'Kinh độ GPS tại thời điểm chụp',
                        type: 'number',
                        format: 'float'
                    ),
                    new OA\Property(
                        property: 'skip_reason',
                        description: 'A3: Lý do không chụp được ảnh.',
                        type: 'string',
                        enum: ['customer_refused', 'device_error', 'other']
                    ),
                    new OA\Property(
                        property: 'note',
                        description: 'A3: Ghi chú thêm.',
                        type: 'string'
                    ),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Xác nhận giao hàng thành công. Trạng thái chuyến xe → COMPLETED.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ride_id',      type: 'string'),
                new OA\Property(property: 'status',       type: 'integer', example: 5),
                new OA\Property(property: 'status_label', type: 'string',  example: 'Đã hoàn thành'),
                new OA\Property(property: 'earnings',     type: 'number',  example: 45000),
                new OA\Property(property: 'photo_url',    type: 'string',  nullable: true),
                new OA\Property(property: 'message',      type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Không tìm thấy chuyến xe')]
    #[OA\Response(response: 422, description: 'Chưa đến đúng vị trí (A6) hoặc điều kiện không thỏa mãn')]
    public function captureDeliveryProof(CaptureDeliveryProofRequest $request): JsonResponse
    {
        $result = $this->rideService->captureDeliveryProof(
            CaptureDeliveryProofDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getData()['message'] ?? 'Xác nhận giao hàng thành công.');
    }

    #[OA\Get(
        path: '/api/v1/driver/rides',
        description: 'Tài xế xem danh sách tất cả các chuyến xe đã nhận theo bộ lọc trạng thái (processing, completed, cancelled) và phân trang.',
        summary: 'Danh sách chuyến xe (Lịch sử/Đang xử lý) của tài xế (UC-51.1)',
        security: [['sanctum' => []]],
        tags: ['Ride']
    )]
    #[OA\Parameter(name: 'status', description: 'Trạng thái lọc (processing, completed, cancelled)', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'per_page', description: 'Số bản ghi trên mỗi trang', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'page', description: 'Số trang', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lấy danh sách thành công')]
    public function getDriverRides(GetDriverRidesRequest $request): JsonResponse
    {
        $result = $this->rideService->getDriverRides(
            GetDriverRidesFilterDTO::fromRequest($request)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Lấy danh sách chuyến xe thành công.');
    }
}
