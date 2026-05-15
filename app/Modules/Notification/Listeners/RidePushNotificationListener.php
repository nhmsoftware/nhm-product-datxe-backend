<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Interfaces\PushNotificationServiceInterface;
use App\Modules\Ride\Events\RideAcceptedByDriver;
use App\Modules\Ride\Events\RideCanceled;
use App\Modules\Ride\Model\Ride;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class RidePushNotificationListener implements ShouldQueue
{
    public function __construct(
        private readonly PushNotificationServiceInterface $pushNotificationService
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof RideAcceptedByDriver) {
            $this->handleRideAccepted($event);
        } elseif ($event instanceof RideCanceled) {
            $this->handleRideCanceled($event);
        }
    }

    private function handleRideAccepted(RideAcceptedByDriver $event): void
    {
        /** @var Ride $ride */
        $ride = $event->ride;
        $customer = $ride->customer;
        
        if ($customer) {
            $this->pushNotificationService->sendToUser(
                $customer,
                'Tài xế đã nhận chuyến',
                "Tài xế {$ride->driver->customerProfile->full_name} đang đến đón bạn.",
                ['ride_id' => $ride->id, 'event' => 'ride.accepted']
            );
        }
    }

    private function handleRideCanceled(RideCanceled $event): void
    {
        /** @var Ride $ride */
        $ride = $event->ride;
        
        // Notify Customer if Driver canceled
        if ($event->canceledBy === 'driver' && $ride->customer) {
            $this->pushNotificationService->sendToUser(
                $ride->customer,
                'Chuyến xe đã bị hủy',
                'Rất tiếc, tài xế đã hủy chuyến xe của bạn.',
                ['ride_id' => $ride->id, 'event' => 'ride.canceled']
            );
        }
        
        // Notify Driver if Customer canceled
        if ($event->canceledBy === 'customer' && $ride->driver) {
            $this->pushNotificationService->sendToUser(
                $ride->driver,
                'Khách hàng đã hủy chuyến',
                'Cuốc xe của bạn đã bị khách hàng hủy.',
                ['ride_id' => $ride->id, 'event' => 'ride.canceled']
            );
        }
    }
}
