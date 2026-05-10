<?php

declare(strict_types=1);

namespace App\Modules\Food\Listeners;

use App\Modules\Food\Events\FoodOrderRated;
use App\Modules\Merchant\Interfaces\MenuRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class UpdateMenuItemRatingStats implements ShouldQueue
{
    public function __construct(
        private readonly MenuRepositoryInterface $menuRepository
    ) {}

    public function handle(FoodOrderRated $event): void
    {
        try {
            foreach ($event->itemsRating as $itemRating) {
                $this->menuRepository->updateRatingStats((string) $itemRating['menu_item_id']);
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
