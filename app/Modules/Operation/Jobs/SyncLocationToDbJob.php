<?php

declare(strict_types=1);

namespace App\Modules\Operation\Jobs;

use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\Enums\UserRole;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job đồng bộ tọa độ từ Redis vào Database định kỳ để giảm tải DB.
 */
final class SyncLocationToDbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $userId ID người dùng
     * @param int $role Role người dùng (UserRole)
     * @param float $lat Vĩ độ
     * @param float $lng Kinh độ
     */
    public function __construct(
        private readonly int $userId,
        private readonly int $role,
        private readonly float $lat,
        private readonly float $lng
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->role === UserRole::Driver->value) {
                DriverProfile::where('user_id', $this->userId)->update([
                    'current_lat' => $this->lat,
                    'current_lng' => $this->lng,
                    'updated_at'  => now(),
                ]);
            } elseif ($this->role === UserRole::Customer->value) {
                CustomerProfile::where('user_id', $this->userId)->update([
                    'current_lat' => $this->lat,
                    'current_lng' => $this->lng,
                    'updated_at'  => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SyncLocationToDbJob failed', [
                'user_id' => $this->userId,
                'error'   => $e->getMessage()
            ]);
        }
    }
}
