<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Driver\Events\DriverApplicationApproved;
use App\Modules\Driver\Events\DriverApplicationRejected;
use App\Modules\Driver\Events\DriverArrivedAtPickup;
use App\Modules\Driver\Events\RideCancelled as DriverRideCancelled;
use App\Modules\Driver\Events\RideCompleted;
use App\Modules\Driver\Events\RidePickedUp;
use App\Modules\Driver\Events\RideStarted;
use App\Modules\Finance\Events\RefundProcessed;
use App\Modules\Finance\Events\VoucherAssigned;
use App\Modules\Food\Events\FoodOrderCreated;
use App\Modules\Merchant\Events\MerchantApproved;
use App\Modules\Notification\Notifications\SystemNotification;
use App\Modules\Order\Events\FoodCancellationRequestHandled;
use App\Modules\Order\Events\FoodOrderStatusUpdated;
use App\Modules\Ride\Events\RideAcceptedByDriver;
use App\Modules\Ride\Events\RideAssignedByAdmin;
use App\Modules\Ride\Events\RideCanceled;
use App\Modules\Ride\Events\RideCancellationRequested;
use App\Modules\Ride\Events\RideCancellationResponded;
use App\Modules\Ride\Model\Ride;
use App\Modules\RiskManagement\Events\UserWarned;
use App\Modules\User\Events\UserStatusUpdated;
use App\Modules\User\Model\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

final class NotificationEventSubscriber implements ShouldQueue
{
    public function subscribe(Dispatcher $events): void
    {
        // Ride Events
        $events->listen(RideAcceptedByDriver::class, [$this, 'handleRideAcceptedByDriver']);
        $events->listen(DriverArrivedAtPickup::class, [$this, 'handleDriverArrivedAtPickup']);
        $events->listen(RidePickedUp::class, [$this, 'handleRidePickedUp']);
        $events->listen(RideStarted::class, [$this, 'handleRideStarted']);
        $events->listen(RideCompleted::class, [$this, 'handleRideCompleted']);
        $events->listen(RideCanceled::class, [$this, 'handleRideCanceled']);
        $events->listen(DriverRideCancelled::class, [$this, 'handleDriverRideCancelled']);
        $events->listen(RideCancellationRequested::class, [$this, 'handleRideCancellationRequested']);
        $events->listen(RideCancellationResponded::class, [$this, 'handleRideCancellationResponded']);
        $events->listen(RideAssignedByAdmin::class, [$this, 'handleRideAssignedByAdmin']);

        // Food & Order Events
        $events->listen(FoodOrderCreated::class, [$this, 'handleFoodOrderCreated']);
        $events->listen(FoodOrderStatusUpdated::class, [$this, 'handleFoodOrderStatusUpdated']);
        $events->listen(FoodCancellationRequestHandled::class, [$this, 'handleFoodCancellationRequestHandled']);

        // User & Admin Events
        $events->listen(UserStatusUpdated::class, [$this, 'handleUserStatusUpdated']);
        $events->listen(UserWarned::class, [$this, 'handleUserWarned']);
        $events->listen(DriverApplicationApproved::class, [$this, 'handleDriverApplicationApproved']);
        $events->listen(DriverApplicationRejected::class, [$this, 'handleDriverApplicationRejected']);
        $events->listen(MerchantApproved::class, [$this, 'handleMerchantApproved']);
        $events->listen(RefundProcessed::class, [$this, 'handleRefundProcessed']);
        $events->listen(VoucherAssigned::class, [$this, 'handleVoucherAssigned']);
    }

    private function notifyUser(string|int $userId, string $title, string $message, string $category, array $extraData = []): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->notify(new SystemNotification($title, $message, $category, $extraData));
        }
    }

    private function notifyCustomerByRide(string $rideId, string $title, string $message, string $category, array $extraData = []): void
    {
        $ride = Ride::find($rideId);
        if ($ride && $ride->customer_id) {
            $this->notifyUser($ride->customer_id, $title, $message, $category, array_merge(['ride_id' => $rideId], $extraData));
        }
    }

    public function handleRideAcceptedByDriver(RideAcceptedByDriver $event): void
    {
        $ride = Ride::with(['driver.driverProfile'])->find($event->rideId);
        if ($ride && $ride->customer_id) {
            $driverName = $ride->driver->full_name ?? '';
            $this->notifyUser(
                $ride->customer_id,
                'Tài xế đã nhận chuyến',
                "Tài xế {$driverName} đang đến đón bạn.",
                'order',
                ['ride_id' => (string) $ride->id, 'event' => 'ride.accepted']
            );
        }
    }

    public function handleDriverArrivedAtPickup(DriverArrivedAtPickup $event): void
    {
        $this->notifyCustomerByRide(
            $event->rideId,
            'Tài xế đã đến',
            'Tài xế của bạn đã đến điểm đón. Vui lòng chuẩn bị để bắt đầu chuyến đi.',
            'order',
            ['event' => 'ride.arrived']
        );
    }

    public function handleRidePickedUp(RidePickedUp $event): void
    {
        $this->notifyCustomerByRide(
            $event->rideId,
            'Đã đón khách',
            'Bạn đã lên xe. Chúc bạn có một chuyến đi vui vẻ!',
            'order',
            ['event' => 'ride.picked_up']
        );
    }

    public function handleRideStarted(RideStarted $event): void
    {
        $this->notifyCustomerByRide(
            $event->rideId,
            'Bắt đầu di chuyển',
            'Chuyến đi của bạn đã bắt đầu.',
            'order',
            ['event' => 'ride.started']
        );
    }

    public function handleRideCompleted(RideCompleted $event): void
    {
        $this->notifyCustomerByRide(
            $event->rideId,
            'Hoàn thành chuyến',
            'Chuyến đi của bạn đã hoàn thành. Cảm ơn bạn đã sử dụng dịch vụ!',
            'order',
            ['event' => 'ride.completed']
        );
    }

    public function handleRideCanceled(RideCanceled $event): void
    {
        $ride = Ride::find($event->rideId);
        if (!$ride) return;

        if ($event->canceledBy === 'driver' && $ride->customer_id) {
            $this->notifyUser(
                $ride->customer_id,
                'Chuyến xe đã bị hủy',
                'Rất tiếc, tài xế đã hủy chuyến xe của bạn.',
                'order',
                ['ride_id' => (string) $ride->id, 'event' => 'ride.canceled']
            );
        }

        if ($event->canceledBy === 'customer' && $ride->driver_id) {
            $this->notifyUser(
                $ride->driver_id,
                'Khách hàng đã hủy chuyến',
                'Cuốc xe của bạn đã bị khách hàng hủy.',
                'order',
                ['ride_id' => (string) $ride->id, 'event' => 'ride.canceled']
            );
        }
    }

    public function handleDriverRideCancelled(DriverRideCancelled $event): void
    {
        $this->notifyCustomerByRide(
            $event->rideId,
            'Tài xế đã hủy chuyến',
            'Tài xế đã hủy chuyến đi của bạn. Vui lòng đặt chuyến mới.',
            'order',
            ['event' => 'ride.driver_cancelled']
        );
    }

    public function handleRideCancellationRequested(RideCancellationRequested $event): void
    {
        $this->notifyUser(
            $event->customerId,
            'Yêu cầu hủy chuyến',
            'Tài xế đang yêu cầu hủy chuyến của bạn. Vui lòng phản hồi.',
            'order',
            ['ride_id' => $event->rideId, 'event' => 'ride.cancellation_requested']
        );
    }

    public function handleRideCancellationResponded(RideCancellationResponded $event): void
    {
        $this->notifyUser(
            $event->driverId,
            'Phản hồi yêu cầu hủy chuyến',
            $event->isApproved ? 'Khách hàng đã đồng ý hủy chuyến.' : 'Khách hàng đã từ chối yêu cầu hủy chuyến.',
            'order',
            ['ride_id' => $event->rideId, 'event' => 'ride.cancellation_responded']
        );
    }

    public function handleRideAssignedByAdmin(RideAssignedByAdmin $event): void
    {
        $this->notifyUser(
            $event->driverId,
            'Được gán chuyến mới',
            'Admin vừa gán cho bạn một chuyến đi mới. Vui lòng kiểm tra.',
            'order',
            ['ride_id' => $event->rideId, 'event' => 'ride.assigned_by_admin']
        );
    }

    public function handleFoodOrderCreated(FoodOrderCreated $event): void
    {
        $this->notifyUser(
            $event->merchantId,
            'Đơn hàng mới',
            'Bạn vừa nhận được một đơn đặt hàng mới!',
            'order',
            ['order_id' => $event->orderId, 'event' => 'food.order_created']
        );
    }

    public function handleFoodOrderStatusUpdated(FoodOrderStatusUpdated $event): void
    {
        $this->notifyUser(
            $event->customerId,
            'Cập nhật trạng thái đơn hàng',
            'Trạng thái đơn hàng đồ ăn của bạn đã thay đổi.',
            'order',
            ['order_id' => $event->orderId, 'event' => 'food_order.updated']
        );

        if ($event->driverId) {
            $this->notifyUser(
                $event->driverId,
                'Cập nhật trạng thái đơn hàng',
                'Trạng thái đơn hàng bạn nhận giao đã thay đổi.',
                'order',
                ['order_id' => $event->orderId, 'event' => 'food_order.updated']
            );
        }
    }

    public function handleFoodCancellationRequestHandled(FoodCancellationRequestHandled $event): void
    {
        $this->notifyUser(
            $event->userId,
            'Xử lý yêu cầu hủy đơn',
            'Yêu cầu hủy đơn đồ ăn của bạn đã được xử lý.',
            'order',
            ['order_id' => $event->orderId, 'event' => 'food_order.cancellation_handled']
        );
    }

    public function handleUserStatusUpdated(UserStatusUpdated $event): void
    {
        $this->notifyUser(
            $event->userId,
            'Cập nhật trạng thái tài khoản',
            $event->isActive ? 'Tài khoản của bạn đã được mở khóa.' : 'Tài khoản của bạn đã bị khóa.',
            'system',
            ['event' => 'user.status_updated']
        );
    }

    public function handleUserWarned(UserWarned $event): void
    {
        $this->notifyUser(
            $event->userId,
            'Cảnh báo vi phạm',
            'Bạn vừa nhận được một cảnh báo vi phạm. Vui lòng kiểm tra chi tiết để tránh bị khóa tài khoản.',
            'system',
            ['event' => 'user.warned']
        );
    }

    public function handleDriverApplicationApproved(DriverApplicationApproved $event): void
    {
        $this->notifyUser(
            $event->userId,
            'Hồ sơ tài xế được duyệt',
            'Chúc mừng! Hồ sơ đăng ký tài xế của bạn đã được duyệt. Bạn có thể bắt đầu nhận chuyến.',
            'system',
            ['event' => 'driver.application_approved']
        );
    }

    public function handleDriverApplicationRejected(DriverApplicationRejected $event): void
    {
        $this->notifyUser(
            $event->userId,
            'Hồ sơ tài xế bị từ chối',
            'Rất tiếc! Hồ sơ đăng ký tài xế của bạn đã bị từ chối. Vui lòng cập nhật lại thông tin.',
            'system',
            ['event' => 'driver.application_rejected']
        );
    }

    public function handleMerchantApproved(MerchantApproved $event): void
    {
        $this->notifyUser(
            $event->userId ?? $event->merchantId,
            'Hồ sơ Merchant được duyệt',
            'Chúc mừng! Hồ sơ Merchant của bạn đã được duyệt.',
            'system',
            ['event' => 'merchant.approved']
        );
    }

    public function handleRefundProcessed(RefundProcessed $event): void
    {
        $this->notifyUser(
            $event->userId,
            'Hoàn tiền thành công',
            'Yêu cầu hoàn tiền của bạn đã được xử lý thành công.',
            'finance',
            ['event' => 'finance.refund.processed']
        );
    }

    public function handleVoucherAssigned(VoucherAssigned $event): void
    {
        $this->notifyUser(
            $event->customerId,
            'Voucher mới',
            'Bạn vừa nhận được một Voucher mới! Nhanh tay sử dụng nhé.',
            'promotion',
            ['event' => 'voucher.assigned', 'voucher_id' => $event->voucherId]
        );
    }
}
