<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Order\Interfaces\AdminOrderServiceInterface;
use App\Modules\Food\Interfaces\FoodOrderRepositoryInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Food\Model\Enums\FoodOrderStatus;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\Ride\DTO\AssignInternalDriverDTO;
use App\Modules\Ride\DTO\BulkPushToPoolDTO;

final class AdminOrderService extends BaseService implements AdminOrderServiceInterface
{
    public function __construct(
        private readonly FoodOrderRepositoryInterface $foodOrderRepository,
        private readonly RideRepositoryInterface $rideRepository,
        private readonly RideServiceInterface $rideService
    ) {}

    /**
     * @inheritDoc
     */
    public function getServiceOrders(): ServiceReturn
    {
        return $this->execute(function (): array {
            // Lấy tất cả FoodOrders thông qua repository
            $foodOrders = $this->foodOrderRepository->listAllFoodOrdersForAdmin();

            // Lọc ra danh sách ride_id đã liên kết với FoodOrders
            $foodOrderRideIds = $foodOrders->pluck('ride_id')->filter()->toArray();

            // Lấy tất cả các cuốc xe Delivery
            $deliveryRides = $this->rideRepository->listDeliveryRidesForAdmin($foodOrderRideIds);

            $mappedOrders = [];

            // Map FoodOrders
            foreach ($foodOrders as $fo) {
                // Ưu tiên kiểm tra trạng thái của chính FoodOrder trước
                // (tránh bị ghi đè bởi trạng thái Ride khi driver_id vẫn còn)
                if ($fo->status === FoodOrderStatus::DELIVERED) {
                    $status = 'completed';
                } elseif ($fo->status === FoodOrderStatus::CANCELLED) {
                    $status = 'canceled';
                } elseif ($fo->ride_id !== null && $fo->ride) {
                    $ride = $fo->ride;
                    // Ride đã gán tài xế và chưa terminal (COMPLETED/CANCELLED)
                    if ($ride->driver_id !== null
                        && !in_array($ride->status, [RideStatus::COMPLETED, RideStatus::CANCELLED], true)
                    ) {
                        $status = 'assigned';
                    } elseif ($ride->status === RideStatus::COMPLETED) {
                        $status = 'completed';
                    } elseif ($ride->status === RideStatus::CANCELLED) {
                        $status = 'canceled';
                    } else {
                        $status = 'waiting';
                    }
                } else {
                    $status = 'waiting';
                }

                $driverName = null;
                if ($fo->ride_id !== null && $fo->ride && $fo->ride->driver) {
                    $driverName = $fo->ride->driver->driverProfile?->full_name ?? $fo->ride->driver->name;
                }

                $mappedOrders[] = [
                    'id'                   => (string) $fo->id,
                    'order_code'           => strtoupper(substr((string) $fo->id, -8)),
                    'customer_name'        => $fo->customer?->customerProfile?->full_name ?? $fo->customer?->name ?? 'Khách hàng',
                    'merchant_name'        => $fo->merchant?->store_name ?? 'Cửa hàng',
                    'pickup_address'       => $fo->merchant?->store_address ?? 'Cửa hàng',
                    'destination_address'  => $fo->delivery_address,
                    'created_at'           => $fo->created_at ? $fo->created_at->toIso8601String() : null,
                    'type'                 => 'Food',
                    'total_amount'         => (float) $fo->total_price,
                    'status'               => $status,
                    'driver_name'          => $driverName,
                    'ride_id'              => $fo->ride_id ? (string) $fo->ride_id : null,
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

            return $mappedOrders;
        });
    }

    /**
     * @inheritDoc
     */
    public function assignDriver(string $orderId, string $driverId): ServiceReturn
    {
        return $this->execute(function () use ($orderId, $driverId) {
            // Check if it is a Food Order
            $foodOrder = $this->foodOrderRepository->findById($orderId);
            if ($foodOrder) {
                if ($foodOrder->ride_id === null) {
                    $foodOrder->load('merchant');
                    $ride = $this->rideRepository->create([
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
                        'ride_type'           => RideType::FOOD_DELIVERY->value,
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
                $ride = $this->rideRepository->findById($orderId);
                $this->validate($ride !== null, 'Không tìm thấy đơn hàng.', 404);
                $rideId = (string) $ride->id;
            }

            // Delegate to RideService
            $dto = new AssignInternalDriverDTO($rideId, $driverId);
            $result = $this->rideService->assignInternalDriver($dto);

            if ($result->isError()) {
                $this->throw($result->getMessage(), $result->getCode());
            }

            return $result->getData();
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function pushToPool(array $orderIds): ServiceReturn
    {
        return $this->execute(function () use ($orderIds) {
            $rideIds = [];

            foreach ($orderIds as $orderId) {
                $foodOrder = $this->foodOrderRepository->findById($orderId);
                if ($foodOrder) {
                    if ($foodOrder->ride_id === null) {
                        $foodOrder->load('merchant');
                        $ride = $this->rideRepository->create([
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
                            'ride_type'           => RideType::FOOD_DELIVERY->value,
                            'status'              => RideStatus::PENDING->value,
                            'base_price'          => $foodOrder->delivery_fee,
                            'total_price'         => $foodOrder->delivery_fee,
                        ]);

                        $foodOrder->ride_id = $ride->id;
                        $foodOrder->save();
                    }
                    $rideIds[] = (string) $foodOrder->ride_id;
                } else {
                    $ride = $this->rideRepository->findById($orderId);
                    if ($ride) {
                        $rideIds[] = (string) $ride->id;
                    }
                }
            }

            $this->validate(!empty($rideIds), 'Không tìm thấy chuyến xe nào để phân phối.', 404);

            $dto = new BulkPushToPoolDTO($rideIds);
            $result = $this->rideService->pushScheduledRidesToPool($dto);

            if ($result->isError()) {
                $this->throw($result->getMessage(), $result->getCode());
            }

            return $result->getData();
        }, useTransaction: true);
    }
}
