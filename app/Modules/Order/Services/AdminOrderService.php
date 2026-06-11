<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Core\Services\BaseService;
use App\Core\Logs\Logging;
use App\Core\Services\ServiceReturn;
use App\Modules\Food\Events\FoodOrderCreated;
use App\Modules\Order\Interfaces\AdminOrderServiceInterface;
use App\Modules\Food\Model\Enums\FoodOrderStatus;
use App\Modules\Food\Model\FoodOrder;
use App\Modules\Food\Interfaces\FoodOrderRepositoryInterface;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\Merchant\Interfaces\MenuItemRepositoryInterface;
use App\Modules\Order\DTO\AdminCreateFoodOrderDTO;
use App\Modules\Order\DTO\AdminUpdateFoodOrderDTO;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\DTO\AssignInternalDriverDTO;
use App\Modules\Ride\DTO\BulkPushToPoolDTO;
use App\Modules\Ride\Services\VehicleTypeCatalogService;
use App\Modules\Order\Model\Enums\OrderType;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\Notification\Notifications\SystemNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AdminOrderService extends BaseService implements AdminOrderServiceInterface
{
    private const FOOD_ORDER_ID_RETRY_TIMES = 3;

    public function __construct(
        private readonly FoodOrderRepositoryInterface $foodOrderRepository,
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly MenuItemRepositoryInterface $menuItemRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RideRepositoryInterface $rideRepository,
        private readonly RideServiceInterface $rideService,
        private readonly VehicleTypeCatalogService $vehicleTypeCatalogService,
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
                    'type'                 => OrderType::FOOD->value,
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
                    'type' => OrderType::DELIVERY->value,
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

    public function getServiceOrderDetail(string $orderId): ServiceReturn
    {
        return $this->execute(function () use ($orderId) {
            $order = $this->foodOrderRepository->getDetail($orderId);
            $this->validate($order !== null, 'Không tìm thấy đơn hàng.', 404);

            return $order;
        });
    }

    public function createFoodOrder(AdminCreateFoodOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $merchant = $this->merchantRepository->findById($dto->merchantId);
            $this->validate($merchant !== null, 'Không tìm thấy nhà hàng.', 404);
            $this->validate((bool) $merchant->is_open, 'Nhà hàng hiện không hoạt động.', 400);

            $itemsData = [];
            foreach ($dto->items as $itemData) {
                $menuItem = $this->menuItemRepository->findItem((string) $itemData['menu_item_id']);
                $this->validate($menuItem !== null && (bool) $menuItem->is_available, 'Món ăn hiện không khả dụng.', 400);

                $options = [];
                foreach (($itemData['options'] ?? []) as $option) {
                    $options[] = [
                        'option_name' => $option['name'],
                        'option_value' => $option['value'],
                        'price' => (float) ($option['price'] ?? 0),
                    ];
                }

                $itemsData[] = [
                    'menu_item_id' => (string) $menuItem->id,
                    'name' => $menuItem->name,
                    'quantity' => (int) $itemData['quantity'],
                    'price' => (float) $menuItem->price,
                    'notes' => $itemData['notes'] ?? null,
                    'options' => $options,
                ];
            }

            $customerId = $this->resolveCustomerId($dto->customerName, $dto->customerPhone);
            $foodOrderStatus = FoodOrderStatus::PENDING;
            $foodOrder = $this->createFoodOrderWithRetry([
                'customer_id' => $customerId,
                'merchant_id' => $dto->merchantId,
                'status' => $foodOrderStatus->value,
                'subtotal_price' => $dto->subtotalPrice,
                'delivery_fee' => $dto->deliveryFee,
                'service_fee' => $dto->serviceFee,
                'discount_amount' => 0,
                'total_price' => $dto->totalPrice,
                'delivery_address' => $dto->deliveryAddress,
                'delivery_lat' => $dto->deliveryLat,
                'delivery_lng' => $dto->deliveryLng,
                'customer_phone' => $dto->customerPhone,
                'notes' => $dto->notes,
                'driver_id' => $dto->driverId,
            ], $itemsData);

            Logging::userActivity(
                action: 'admin_create_food_order',
                description: sprintf('Tạo đơn đồ ăn #%s cho nhà hàng #%s', $foodOrder->id, $dto->merchantId),
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            event(new FoodOrderCreated(
                orderId: (string) $foodOrder->id,
                customerId: (string) $foodOrder->customer_id,
                merchantId: (string) $foodOrder->merchant_id,
                totalPrice: (float) $foodOrder->total_price
            ));

            $this->notifyMerchantAndOptionalDriver($foodOrder, $dto->driverId, 'Đơn đồ ăn mới', 'Bạn vừa có một đơn đồ ăn mới, vui lòng kiểm tra.');

            return $this->success($foodOrder->load(['items.options', 'customer.customerProfile', 'merchant', 'ride.driver.driverProfile']), 'Tạo đơn đồ ăn thành công.');
        }, useTransaction: true);
    }

    public function updateFoodOrder(AdminUpdateFoodOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $order = FoodOrder::with(['items.options', 'ride.driver.driverProfile'])->find($dto->orderId);
            $this->validate($order !== null, 'Không tìm thấy đơn hàng.', 404);
            $this->validate(!$order->status->isTerminal(), 'Không thể cập nhật đơn đồ ăn lúc này. Vui lòng thử lại.', 400);

            $merchant = $this->merchantRepository->findById($dto->merchantId);
            $this->validate($merchant !== null, 'Không tìm thấy nhà hàng.', 404);
            $this->validate((bool) $merchant->is_open, 'Nhà hàng hiện không hoạt động.', 400);

            $itemsData = [];
            foreach ($dto->items as $itemData) {
                $menuItem = $this->menuItemRepository->findItem((string) $itemData['menu_item_id']);
                $this->validate($menuItem !== null && (bool) $menuItem->is_available, 'Một hoặc nhiều món ăn không còn khả dụng.', 400);

                $options = [];
                foreach (($itemData['options'] ?? []) as $option) {
                    $options[] = [
                        'option_name' => $option['name'],
                        'option_value' => $option['value'],
                        'price' => (float) ($option['price'] ?? 0),
                    ];
                }

                $itemsData[] = [
                    'menu_item_id' => (string) $menuItem->id,
                    'name' => $menuItem->name,
                    'quantity' => (int) $itemData['quantity'],
                    'price' => (float) $menuItem->price,
                    'notes' => $itemData['notes'] ?? null,
                    'options' => $options,
                ];
            }

            $order->update([
                'merchant_id' => $dto->merchantId,
                'delivery_address' => $dto->deliveryAddress,
                'delivery_lat' => $dto->deliveryLat,
                'delivery_lng' => $dto->deliveryLng,
                'notes' => $dto->notes,
                'subtotal_price' => $dto->subtotalPrice,
                'delivery_fee' => $dto->deliveryFee,
                'service_fee' => $dto->serviceFee,
                'total_price' => $dto->totalPrice,
                'driver_id' => $dto->driverId,
            ]);

            $order->items()->delete();
            foreach ($itemsData as $itemData) {
                $options = $itemData['options'];
                unset($itemData['options']);
                $item = $order->items()->create($itemData);
                foreach ($options as $option) {
                    $item->options()->create($option);
                }
            }

            $order = $order->fresh(['items.options', 'customer.customerProfile', 'merchant', 'ride.driver.driverProfile']);

            if (!$dto->driverId && $order->ride && $order->ride->driver_id !== null) {
                $this->rideRepository->releaseDriverFromRide($order->ride->id, 'Admin unassigned driver from food order');
                $this->rideRepository->updateStatus($order->ride->id, RideStatus::PENDING);
            } elseif ($dto->driverId && $order->ride && (string) $order->ride->driver_id !== (string) $dto->driverId) {
                if ($order->ride->driver_id !== null) {
                    $this->rideRepository->releaseDriverFromRide($order->ride->id, 'Admin changed driver for food order');
                    $this->rideRepository->updateStatus($order->ride->id, RideStatus::PENDING);
                }
                $this->rideService->assignInternalDriver(new AssignInternalDriverDTO((string) $order->ride->id, $dto->driverId));
            }

            $this->notifyMerchantAndOptionalDriver($order, $dto->driverId, 'Đơn đồ ăn được cập nhật', 'Đơn đồ ăn của bạn đã được cập nhật.');

            Logging::userActivity(
                action: 'admin_update_food_order',
                description: sprintf('Cập nhật đơn đồ ăn #%s', $order->id),
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success($order, 'Cập nhật đơn đồ ăn thành công.');
        }, useTransaction: true);
    }

    public function cancelFoodOrder(string $orderId, ?string $reason = null): ServiceReturn
    {
        return $this->execute(function () use ($orderId, $reason) {
            $order = FoodOrder::with(['ride.customer', 'ride.driver'])->find($orderId);
            $this->validate($order !== null, 'Không tìm thấy đơn hàng.', 404);
            $this->validate(!$order->status->isTerminal(), 'Không thể hủy đơn đồ ăn đã hoàn thành.', 400);

            $oldStatus = $order->status->value;
            $order->update([
                'status' => FoodOrderStatus::CANCELLED->value,
            ]);

            if ($order->ride) {
                $this->rideRepository->cancel((string) $order->ride->id, $reason, 0);
            }

            $this->notifyMerchantAndOptionalDriver($order, $order->ride?->driver_id, 'Đơn đồ ăn đã bị hủy', $reason ? "Đơn đồ ăn đã bị hủy. Lý do: {$reason}" : 'Đơn đồ ăn đã bị hủy.');

            Logging::userActivity(
                action: 'admin_cancel_food_order',
                description: sprintf('Hủy đơn đồ ăn #%s%s', $order->id, $reason ? " với lý do: {$reason}" : ''),
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success([
                'order_id' => (string) $order->id,
                'status' => FoodOrderStatus::CANCELLED->value,
                'status_label' => FoodOrderStatus::CANCELLED->getLabel(),
                'old_status' => $oldStatus,
            ], 'Hủy đơn đồ ăn thành công.');
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function assignDriver(string $orderId, string $driverId): ServiceReturn
    {
        return $this->execute(function () use ($orderId, $driverId) {
            $bikeVehicleTypeId = $this->vehicleTypeCatalogService->getIdByCode('bike');
            $this->validate($bikeVehicleTypeId !== null, 'Không tìm thấy loại xe giao đồ ăn.', 500);

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
                        'vehicle_type'        => $bikeVehicleTypeId,
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
            $bikeVehicleTypeId = $this->vehicleTypeCatalogService->getIdByCode('bike');
            $this->validate($bikeVehicleTypeId !== null, 'Không tìm thấy loại xe giao đồ ăn.', 500);

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
                            'vehicle_type'        => $bikeVehicleTypeId,
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

    private function resolveCustomerId(string $name, string $phone): string
    {
        $resolved = $this->userRepository->findByPhone($phone);
        if ($resolved) {
            $this->validate($resolved->role === UserRole::Customer, 'Số điện thoại này đã được sử dụng bởi tài khoản khác.', 409);
            if ($resolved->customerProfile === null) {
                $this->userRepository->createCustomerProfile($resolved, ['full_name' => $name]);
            }
            return (string) $resolved->id;
        }

        $newUser = $this->userRepository->create([
            'phone' => $phone,
            'password' => Hash::make(sprintf('Tmp@%06d', random_int(0, 999999))),
            'role' => UserRole::Customer,
            'is_verified' => true,
            'is_phone_verified' => true,
            'is_active' => true,
        ]);
        $this->userRepository->createCustomerProfile($newUser, ['full_name' => $name]);

        return (string) $newUser->id;
    }

    private function createFoodOrderWithRetry(array $orderData, array $itemsData): FoodOrder
    {
        for ($attempt = 1; $attempt <= self::FOOD_ORDER_ID_RETRY_TIMES; $attempt++) {
            try {
                return $this->foodOrderRepository->createOrder($orderData, $itemsData);
            } catch (QueryException $e) {
                if ($this->isDuplicateFoodOrderId($e) && $attempt < self::FOOD_ORDER_ID_RETRY_TIMES) {
                    continue;
                }

                if ($this->isDuplicateFoodOrderId($e)) {
                    $this->throw('Không thể tạo mã đơn đồ ăn. Vui lòng thử lại.', 500);
                }

                throw $e;
            }
        }

        $this->throw('Không thể tạo mã đơn đồ ăn. Vui lòng thử lại.', 500);
    }

    private function isDuplicateFoodOrderId(QueryException $e): bool
    {
        $message = Str::lower($e->getMessage());

        return str_contains($message, 'duplicate key value violates unique constraint')
            || (str_contains($message, 'duplicate entry') && str_contains($message, 'primary'));
    }

    private function notifyMerchantAndOptionalDriver(FoodOrder $order, ?string $driverId, string $title, string $message): void
    {
        $merchant = $this->merchantRepository->findById((string) $order->merchant_id);
        if ($merchant?->user) {
            $merchant->user->notify(new SystemNotification(
                $title,
                $message,
                'order',
                ['order_id' => (string) $order->id, 'event' => 'food.order.updated']
            ));
        }

        if ($driverId) {
            $driver = $this->userRepository->findById($driverId);
            $driver?->notify(new SystemNotification(
                $title,
                $message,
                'order',
                ['order_id' => (string) $order->id, 'event' => 'food.order.updated']
            ));
        }
    }
}
