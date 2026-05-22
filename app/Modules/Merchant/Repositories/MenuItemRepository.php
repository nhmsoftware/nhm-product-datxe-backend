<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Merchant\Interfaces\MenuItemRepositoryInterface;
use App\Modules\Merchant\Model\MenuItem;
use Illuminate\Support\Collection;

final class MenuItemRepository extends BaseRepository implements MenuItemRepositoryInterface
{
    public function getModel(): string
    {
        return MenuItem::class;
    }

    public function findItem(string $itemId): ?MenuItem
    {
        return $this->getQuery()->find($itemId);
    }

    public function getItemsByCategory(string $categoryId): Collection
    {
        return $this->getQuery()
            ->where('category_id', $categoryId)
            ->orderBy('order')
            ->get();
    }

    public function createItem(array $data, array $sizes = [], array $toppings = []): MenuItem
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $sizes, $toppings) {
            /** @var MenuItem $item */
            $item = $this->create($data);

            if (!empty($sizes)) {
                $item->sizes()->createMany($sizes);
            }

            if (!empty($toppings)) {
                $item->toppings()->createMany($toppings);
            }

            return $item;
        });
    }

    public function updateItem(string $itemId, array $data, array $sizes = [], array $toppings = []): MenuItem
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($itemId, $data, $sizes, $toppings) {
            /** @var MenuItem $item */
            $item = $this->getQuery()->findOrFail($itemId);
            $item->update($data);

            // Simple sync: delete then recreate
            $item->sizes()->delete();
            if (!empty($sizes)) {
                $item->sizes()->createMany($sizes);
            }

            $item->toppings()->delete();
            if (!empty($toppings)) {
                $item->toppings()->createMany($toppings);
            }

            return $item;
        });
    }

    public function isNameExistsInCategory(string $merchantProfileId, string $categoryId, string $name, ?string $excludeItemId = null): bool
    {
        $query = $this->getQuery()
            ->where('merchant_profile_id', $merchantProfileId)
            ->where('category_id', $categoryId)
            ->where('name', $name);

        if ($excludeItemId) {
            $query->where('id', '!=', $excludeItemId);
        }

        return $query->exists();
    }

    public function deleteItem(string $itemId): bool
    {
        return (bool) $this->getQuery()->findOrFail($itemId)->delete();
    }

    public function updateItemStatus(string $itemId, bool $isAvailable): bool
    {
        return (bool) $this->getQuery()->where('id', $itemId)->update([
            'is_available' => $isAvailable
        ]);
    }

    public function updateRatingStats(string $itemId, float $rating, int $totalReviews): bool
    {
        return (bool) $this->getQuery()->where('id', $itemId)->update([
            'rating'        => $rating,
            'total_reviews' => $totalReviews,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function findItemByName(string $merchantProfileId, string $categoryId, string $name): ?MenuItem
    {
        return $this->getQuery()
            ->where('merchant_profile_id', $merchantProfileId)
            ->where('category_id', $categoryId)
            ->where('name', $name)
            ->first();
    }
}
