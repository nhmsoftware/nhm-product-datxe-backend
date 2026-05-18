<?php

declare(strict_types=1);

namespace App\Modules\Food\Listeners;

use App\Modules\Food\Events\FoodOrderRated;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

final class UpdateMerchantRatingStats implements ShouldQueue
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository
    ) {}

    public function handle(FoodOrderRated $event): void
    {
        try {
            $merchantId = (string) $event->merchantId;
            
            $stats = \App\Modules\Food\Model\FoodRating::query()
                ->where('merchant_id', $merchantId)
                ->select([
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('AVG(rating) as average_rating')
                ])
                ->first();

            $this->merchantRepository->updateRatingStats(
                $merchantId,
                (float) ($stats->average_rating ?? 0.0),
                (int) ($stats->total_orders ?? 0)
            );
            
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
