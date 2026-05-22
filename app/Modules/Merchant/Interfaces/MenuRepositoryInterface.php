<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Merchant\Model\MenuCategory;
use Illuminate\Support\Collection;

interface MenuRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all categories with items for a specific merchant.
     *
     * @param string $merchantProfileId
     * @return Collection
     */
    public function getFullMenu(string $merchantProfileId): Collection;

    /**
     * Get all categories with active items, sizes, and toppings for customer view.
     *
     * @param string $merchantProfileId
     * @return Collection
     */
    public function getFullMenuForCustomer(string $merchantProfileId): Collection;

    /**
     * Find a category by name or create it if it doesn't exist.
     *
     * @param string $merchantProfileId
     * @param string $name
     * @return MenuCategory
     */
    public function findOrCreateCategory(string $merchantProfileId, string $name): MenuCategory;

    /**
     * Get categories list for a merchant (without items).
     *
     * @param string $merchantProfileId
     * @return Collection
     */
    public function getCategories(string $merchantProfileId): Collection;
}
