<?php

declare(strict_types=1);

namespace App\Modules\Operation\Listeners;

use App\Modules\Operation\Interfaces\DispatchServiceInterface;
use App\Modules\Ride\Events\RideBooked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class StartRideDispatching implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly DispatchServiceInterface $dispatchService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(RideBooked $event): void
    {
        $this->dispatchService->initiateDispatch((string) $event->rideId);
    }
}
