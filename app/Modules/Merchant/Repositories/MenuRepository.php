<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Repositories;

 use App\Core\Repository\BaseRepository;
 use App\Modules\Merchant\Interfaces\MenuRepositoryInterface;
 use App\Modules\Merchant\Model\MenuCategory;
 use Illuminate\Support\Collection;

 final class MenuRepository extends BaseRepository implements MenuRepositoryInterface
 {
     public function getModel(): string
     {
         return MenuCategory::class;
     }

     public function getFullMenu(string $merchantProfileId): Collection
     {
         return $this->getQuery()
             ->where('merchant_profile_id', $merchantProfileId)
             ->with(['items' => function ($query) {
                 $query->orderBy('order');
             }])
             ->orderBy('order')
             ->get();
     }

     public function getFullMenuForCustomer(string $merchantProfileId): Collection
     {
          return $this->getQuery()
              ->where('merchant_profile_id', $merchantProfileId)
              ->with(['items' => function ($query) {
                  $query->where('is_available', true)
                      ->with(['sizes', 'toppings'])
                      ->orderBy('order');
              }])
              ->orderBy('order')
              ->get();
     }

     public function findOrCreateCategory(string $merchantProfileId, string $name): MenuCategory
     {
         return $this->getQuery()->firstOrCreate([
             'merchant_profile_id' => $merchantProfileId,
             'name'                => $name,
         ]);
     }

    public function getCategories(string $merchantProfileId): Collection
    {
        return $this->getQuery()
            ->where('merchant_profile_id', $merchantProfileId)
            ->select(['id', 'merchant_profile_id', 'name', 'order', 'is_active'])
            ->orderBy('order')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function findCategoryByName(string $merchantProfileId, string $name): ?MenuCategory
    {
        return $this->getQuery()
            ->where('merchant_profile_id', $merchantProfileId)
            ->where('name', $name)
            ->first();
    }
 }
