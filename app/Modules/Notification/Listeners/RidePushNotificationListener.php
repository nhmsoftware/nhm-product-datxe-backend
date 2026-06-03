<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Notifications\RideNotification;
use App\Modules\Ride\Events\RideAcceptedByDriver;
use App\Modules\Ride\Events\RideCanceled;
use App\Modules\Driver\Events\RideCancelled;
use App\Modules\Ride\Model\Ride;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class RidePushNotificationListener implements ShouldQueue
{
    public function handle(object $event): void
    {
        if ($event instanceof RideAcceptedByDriver) {
            $this->handleRideAccepted($event);
        } elseif ($event instanceof RideCanceled) {
            $this->handleRideCanceled($event);
        } elseif ($event instanceof RideCancelled) {
            $this->handleRideDriverCancelled($event);
        }
    }

    private function handleRideAccepted(RideAcceptedByDriver $event): void
    {
        /** @var Ride|null $ride */
        $ride = Ride::with(['customer', 'driver.driverProfile'])->find($event->rideId);
        if (!$ride) {
            Log::error("[RidePushNotificationListener] Ride not found for acceptance: {$event->rideId}");
            return;
        }
        $customer = $ride->customer;

        if ($customer) {
            $driverName = $ride->driver->full_name ?? '';
            $customer->notify(new RideNotification(
                'Tài xế đã nhận chuyến',
                "Tài xế {$driverName} đang đến đón bạn.",
                'order',
                ['ride_id' => (string) $ride->id, 'event' => 'ride.accepted']
            ));
        }
    }

    private function handleRideCanceled(RideCanceled $event): void
    {
        /** @var Ride|null $ride */
        $ride = Ride::with(['customer', 'driver'])->find($event->rideId);
        if (!$ride) {
            Log::error("[RidePushNotificationListener] Ride not found for cancellation: {$event->rideId}");
            return;
        }

        // Notify Customer if Driver canceled
        if ($event->canceledBy === 'driver' && $ride->customer) {
            $ride->customer->notify(new RideNotification(
                'Chuyến xe đã bị hủy',
                'Rất tiếc, tài xế đã hủy chuyến xe của bạn.',
                'order',
                ['ride_id' => (string) $ride->id, 'event' => 'ride.canceled']
            ));
        }

        // Notify Driver if Customer canceled
        if ($event->canceledBy === 'customer' && $ride->driver) {
            $ride->driver->notify(new RideNotification(
                'Khách hàng đã hủy chuyến',
                'Cuốc xe của bạn đã bị khách hàng hủy.',
                'order',
                ['ride_id' => (string) $ride->id, 'event' => 'ride.canceled']
            ));
        }
    }

    private function handleRideDriverCancelled(RideCancelled $event): void
    {
        /** @var Ride|null $ride */
        $ride = Ride::with(['customer'])->find($event->rideId);
        if (!$ride) {
            Log::error("[RidePushNotificationListener] Không tìm thấy chuyến đi để hủy tài xế: {$event->rideId}");
            return;
        }

        if ($ride->customer) {
            $ride->customer->notify(new RideNotification(
                'Tài xế đã hủy chuyến',
                'Tài xế đã hủy chuyến đi của bạn. Hệ thống đang tìm tài xế khác.',
                'order',
                ['ride_id' => (string) $ride->id, 'event' => 'ride.driver_cancelled']
            ));
        }
    }
}
