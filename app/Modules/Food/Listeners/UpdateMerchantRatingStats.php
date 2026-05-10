<?php

declare(strict_types=1);

namespace App\Modules\Food\Listeners;

use App\Modules\Food\Events\FoodOrderRated;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class UpdateMerchantRatingStats implements ShouldQueue
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository
    ) {}

    public function handle(FoodOrderRated $event): void
    {
        try {
            $this->merchantRepository->updateRatingStats((string) $event->merchantId);
            
            Log::info('Merchant rating stats updated', [
                'merchant_id' => $event->merchantId,
                'order_id'    => $event->orderId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update merchant rating stats', [
                'error'       => $e->getMessage(),
                'merchant_id' => $event->merchantId
            ]);
        }
    }
}
