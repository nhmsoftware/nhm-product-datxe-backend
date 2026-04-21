<?php

declare(strict_types=1);

namespace App\Modules\Operation\Jobs;

use App\Modules\Operation\Interfaces\DispatchServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PriorityDispatchFallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $rideId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(DispatchServiceInterface $dispatchService): void
    {
        $dispatchService->fallbackToPartnerDrivers($this->rideId);
    }
}
