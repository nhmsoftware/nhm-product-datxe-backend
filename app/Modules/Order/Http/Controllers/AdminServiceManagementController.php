<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Food\Model\FoodOrder;
use App\Modules\Ride\Model\Ride;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\Food\Model\Enums\FoodOrderStatus;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Ride\DTO\AssignInternalDriverDTO;
use App\Modules\Ride\DTO\BulkPushToPoolDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AdminServiceManagementController extends BaseController
{
    public function __construct(
        private readonly RideServiceInterface $rideService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/services/orders',
        summary: 'Lấy danh sách đơn hàng dịch vụ (Admin)',
        description: 'Lấy danh sách các đơn hàng đồ ăn và giao hàng cho quản trị viên.',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền Admin')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        // Fetch all FoodOrders
        $foodOrders = FoodOrder::with(['customer.customerProfile', 'merchant', 'ride.driver.driverProfile'])
            ->latest()
            ->get();

        // Fetch all Delivery Rides
        $deliveryRides = Ride::where('ride_type', RideType::DELIVERY->value)
            ->with(['customer.customerProfile', 'driver.driverProfile'])
            ->latest()
            ->get();

        $mappedOrders = [];

        // Map FoodOrders
        foreach ($foodOrders as $fo) {
            $status = 'waiting';
            if ($fo->status === FoodOrderStatus::CANCELLED->value) {
                $status = 'canceled';
            } elseif ($fo->status === FoodOrderStatus::DELIVERED->value) {
                $status = 'completed';
            } elseif ($fo->ride_id !== null && $fo->ride) {
                $ride = $fo->ride;
                if ($ride->driver_id !== null) {
                    $status = 'assigned';
                } elseif ($ride->status === RideStatus::COMPLETED) {
                    $status = 'completed';
                } elseif ($ride->status === RideStatus::CANCELLED) {
                    $status = 'canceled';
                }
            }

            $driverName = null;
            if ($fo->ride_id !== null && $fo->ride && $fo->ride->driver) {
                $driverName = $fo->ride->driver->driverProfile?->full_name ?? $fo->ride->driver->name;
            }

            $mappedOrders[] = [
                'id' => (string) $fo->id,
                'order_code' => strtoupper(substr((string) $fo->id, -8)),
                'customer_name' => $fo->customer?->customerProfile?->full_name ?? $fo->customer?->name ?? 'Khách hàng',
                'merchant_name' => $fo->merchant?->store_name ?? 'Cửa hàng',
                'pickup_address' => $fo->merchant?->store_address ?? 'Cửa hàng',
                'destination_address' => $fo->delivery_address,
                'created_at' => $fo->created_at ? $fo->created_at->toIso8601String() : null,
                'type' => 'Food',
                'total_amount' => (float) $fo->total_price,
                'status' => $status,
                'driver_name' => $driverName,
            ];
        }

        // Map Delivery Rides
        foreach ($deliveryRides as $ride) {
            $status = 'waiting';
            $rs = $ride->status;
            if ($rs === RideStatus::COMPLETED) {
                $status = 'completed';
            } elseif ($rs === RideStatus::CANCELLED || $rs === RideStatus::CANCELLATION_REQUESTED) {
                $status = 'canceled';
            } elseif ($rs === RideStatus::ACCEPTED || $rs === RideStatus::PICKED_UP || $rs === RideStatus::IN_PROGRESS) {
                $status = $ride->driver_id !== null ? 'assigned' : 'waiting';
            }

            $driverName = null;
            if ($ride->driver) {
                $driverName = $ride->driver->driverProfile?->full_name ?? $ride->driver->name;
            }

            $mappedOrders[] = [
                'id' => (string) $ride->id,
                'order_code' => (string) $ride->id,
                'customer_name' => $ride->customer?->customerProfile?->full_name ?? $ride->customer?->name ?? 'Khách hàng',
                'merchant_name' => null,
                'pickup_address' => $ride->pickup_address,
                'destination_address' => $ride->destination_address,
                'created_at' => $ride->created_at ? $ride->created_at->toIso8601String() : null,
                'type' => 'Delivery',
                'total_amount' => (float) $ride->total_price,
                'status' => $status,
                'driver_name' => $driverName,
            ];
        }

        // Sort all orders by created_at descending
        usort($mappedOrders, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        return $this->sendSuccess($mappedOrders, 'Lấy danh sách đơn hàng dịch vụ thành công.');
    }

    #[OA\Post(
        path: '/api/v1/admin/services/orders/assign',
        summary: 'Chỉ định tài xế cho đơn hàng dịch vụ (Admin)',
        description: 'Gán trực tiếp một tài xế cho đơn hàng đồ ăn hoặc giao hàng.',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'driver_id'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'string', example: '123'),
                    new OA\Property(property: 'driver_id', type: 'string', example: '456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ')
        ]
    )]
    public function assign(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');
        $driverId = $request->input('driver_id');

        if (empty($orderId) || empty($driverId)) {
            return $this->sendError('Thiếu thông tin đơn hàng hoặc tài xế.', 400);
        }

        // Check if it is a Food Order
        $foodOrder = FoodOrder::find($orderId);
        if ($foodOrder) {
            if ($foodOrder->ride_id === null) {
                $foodOrder->load('merchant');
                $ride = Ride::create([
                    'customer_id'         => $foodOrder->customer_id,
                    'pickup_address'      => $foodOrder->merchant?->store_address ?? 'Cửa hàng',
                    'pickup_lat'          => $foodOrder->merchant?->latitude ?? 0,
                    'pickup_lng'          => $foodOrder->merchant?->longitude ?? 0,
                    'destination_address' => $foodOrder->delivery_address,
                    'destination_lat'     => $foodOrder->delivery_lat ?? 0,
                    'destination_lng'     => $foodOrder->delivery_lng ?? 0,
                    'distance'            => 0,
                    'duration'            => 0,
                    'vehicle_type'        => VehicleType::BIKE->value,
                    'ride_type'           => RideType::DELIVERY->value,
                    'status'              => RideStatus::PENDING->value,
                    'base_price'          => $foodOrder->delivery_fee,
                    'total_price'         => $foodOrder->delivery_fee,
                ]);

                $foodOrder->ride_id = $ride->id;
                $foodOrder->save();
            }

            $rideId = (string) $foodOrder->ride_id;
        } else {
            // Check if it is a Delivery Order (Ride)
            $ride = Ride::find($orderId);
            if (!$ride) {
                return $this->sendError('Không tìm thấy đơn hàng.', 404);
            }
            $rideId = (string) $ride->id;
        }

        // Delegate to RideService
        $dto = new AssignInternalDriverDTO($rideId, (string) $driverId);
        $result = $this->rideService->assignInternalDriver($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Phân phối chuyến xe thành công.');
    }

    #[OA\Post(
        path: '/api/v1/admin/services/orders/push-to-pool',
        summary: 'Đẩy đơn hàng dịch vụ ra pool (Admin)',
        description: 'Đẩy một hoặc nhiều đơn hàng dịch vụ vào pool tìm tài xế.',
        security: [['sanctum' => []]],
        tags: ['Admin Service Management'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_ids'],
                properties: [
                    new OA\Property(property: 'order_ids', type: 'array', items: new OA\Items(type: 'string'), example: ['123', '456']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công')
        ]
    )]
    public function pushToPool(Request $request): JsonResponse
    {
        $orderIds = $request->input('order_ids');

        if (empty($orderIds) || !is_array($orderIds)) {
            return $this->sendError('Danh sách đơn hàng không hợp lệ.', 400);
        }

        $rideIds = [];

        foreach ($orderIds as $orderId) {
            $foodOrder = FoodOrder::find($orderId);
            if ($foodOrder) {
                if ($foodOrder->ride_id === null) {
                    $foodOrder->load('merchant');
                    $ride = Ride::create([
                        'customer_id'         => $foodOrder->customer_id,
                        'pickup_address'      => $foodOrder->merchant?->store_address ?? 'Cửa hàng',
                        'pickup_lat'          => $foodOrder->merchant?->latitude ?? 0,
                        'pickup_lng'          => $foodOrder->merchant?->longitude ?? 0,
                        'destination_address' => $foodOrder->delivery_address,
                        'destination_lat'     => $foodOrder->delivery_lat ?? 0,
                        'destination_lng'     => $foodOrder->delivery_lng ?? 0,
                        'distance'            => 0,
                        'duration'            => 0,
                        'vehicle_type'        => VehicleType::BIKE->value,
                        'ride_type'           => RideType::DELIVERY->value,
                        'status'              => RideStatus::PENDING->value,
                        'base_price'          => $foodOrder->delivery_fee,
                        'total_price'         => $foodOrder->delivery_fee,
                    ]);

                    $foodOrder->ride_id = $ride->id;
                    $foodOrder->save();
                }
                $rideIds[] = (string) $foodOrder->ride_id;
            } else {
                $ride = Ride::find($orderId);
                if ($ride) {
                    $rideIds[] = (string) $ride->id;
                }
            }
        }

        if (empty($rideIds)) {
            return $this->sendError('Không tìm thấy chuyến xe nào để phân phối.', 404);
        }

        $dto = new BulkPushToPoolDTO($rideIds);
        $result = $this->rideService->pushScheduledRidesToPool($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Phân phối chuyến xe ra pool thành công.');
    }
}
