<?php

declare(strict_types=1);

namespace App\Modules\Ride\Listeners;

use App\Modules\Ride\Events\RideAcceptedByDriver;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\Notification\Interfaces\PushNotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class SendFCMPushNotificationOnRideAccepted implements ShouldQueue
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PushNotificationServiceInterface $pushService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(RideAcceptedByDriver $event): void
    {
        try {
            // Find customer
            $customer = $this->userRepository->findDetailById($event->customerId);
            // Find driver
            $driver = $this->userRepository->findDriverWithProfileById($event->driverId);

            if (!$customer || !$driver || !$driver->driverProfile) {
                return;
            }

            $title = 'Tài xế đã nhận chuyến!';
            $body = "Tài xế {$driver->driverProfile->full_name} đang trên đường đến đón bạn.";

            // Dữ liệu đính kèm (FCM data payload)
            $data = [
                'event'       => 'ride.accepted',
                'ride_id'     => (string) $event->rideId,
                'driver_name' => $driver->driverProfile->full_name,
                'driver_phone'=> $driver->phone,
            ];

            // Gọi service gửi FCM cho Customer
            $success = $this->pushService->sendToUser($customer, $title, $body, $data);

            if ($success) {
                Log::info('FCM Push Notification sent for ride.accepted', [
                    'ride_id' => $event->rideId,
                    'customer_id' => $event->customerId
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SendFCMPushNotificationOnRideAccepted failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
