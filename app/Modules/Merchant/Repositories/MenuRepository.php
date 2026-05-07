<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Merchant\Interfaces\MenuRepositoryInterface;
use App\Modules\Merchant\Model\MenuCategory;
use App\Modules\Merchant\Model\MenuItem;
use Illuminate\Support\Collection;

final class MenuRepository extends BaseRepository implements MenuRepositoryInterface
{
    public function getModel(): string
    {
        return MenuCategory::class;
    }

    public function findItem(string $itemId): ?MenuItem
    {
        return MenuItem::find($itemId);
    }

    public function getFullMenu(string $merchantProfileId): Collection
    {
        return $this->model
            ->where('merchant_profile_id', $merchantProfileId)
            ->with(['items' => function ($query) {
                $query->orderBy('order');
            }])
            ->orderBy('order')
            ->get();
    }

    public function getItemsByCategory(string $categoryId): Collection
    {
        return MenuItem::where('category_id', $categoryId)
            ->orderBy('order')
            ->get();
    }

    public function findOrCreateCategory(string $merchantProfileId, string $name): MenuCategory
    {
        return MenuCategory::firstOrCreate([
            'merchant_profile_id' => $merchantProfileId,
            'name'                => $name,
        ]);
    }

    public function createItem(array $data, array $sizes = [], array $toppings = []): MenuItem
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $sizes, $toppings) {
            /** @var MenuItem $item */
            $item = MenuItem::create($data);

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
            $item = MenuItem::findOrFail($itemId);
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
        $query = MenuItem::where('merchant_profile_id', $merchantProfileId)
            ->where('category_id', $categoryId)
            ->where('name', $name);

        if ($excludeItemId) {
            $query->where('id', '!=', $excludeItemId);
        }

        return $query->exists();
    }

    public function deleteItem(string $itemId): bool
    {
        return (bool) MenuItem::findOrFail($itemId)->delete();
    }
}
