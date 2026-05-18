<?php

declare(strict_types=1);

namespace App\Modules\Food\Listeners;

use App\Modules\Food\Events\FoodOrderRated;
use App\Modules\Merchant\Interfaces\MenuItemRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

final class UpdateMenuItemRatingStats implements ShouldQueue
{
    public function __construct(
        private readonly MenuItemRepositoryInterface $menuItemRepository
    ) {}

    public function handle(FoodOrderRated $event): void
    {
        try {
            foreach ($event->itemsRating as $itemRating) {
                $itemId = (string) $itemRating['menu_item_id'];
                
                $stats = \App\Modules\Food\Model\FoodItemRating::query()
                    ->where('menu_item_id', $itemId)
                    ->select([
                        DB::raw('COUNT(*) as total_reviews'),
                        DB::raw('AVG(rating) as rating')
                    ])
                    ->first();

                $this->menuItemRepository->updateRatingStats(
                    $itemId,
                    (float) ($stats->rating ?? 0.0),
                    (int) ($stats->total_reviews ?? 0)
                );
            }
            
            Log::info('Menu items rating stats updated', [
                'order_id' => $event->orderId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update menu items rating stats', [
                'error'    => $e->getMessage(),
                'order_id' => $event->orderId
            ]);
        }
    }
}
