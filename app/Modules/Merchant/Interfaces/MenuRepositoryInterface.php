<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
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
     * Find a menu item by ID.
     *
     * @param string $itemId
     * @return \App\Modules\Merchant\Model\MenuItem|null
     */
    public function findItem(string $itemId): ?\App\Modules\Merchant\Model\MenuItem;

    /**
     * Get menu items by category.
     *
     * @param string $categoryId
     * @return Collection
     */
    public function getItemsByCategory(string $categoryId): Collection;

    /**
     * Find a category by name or create it if it doesn't exist.
     *
     * @param string $merchantProfileId
     * @param string $name
     * @return \App\Modules\Merchant\Model\MenuCategory
     */
    public function findOrCreateCategory(string $merchantProfileId, string $name): \App\Modules\Merchant\Model\MenuCategory;

    /**
     * Create a new menu item with sizes and toppings.
     *
     * @param array $data
     * @param array $sizes
     * @param array $toppings
     * @return \App\Modules\Merchant\Model\MenuItem
     */
    public function createItem(array $data, array $sizes = [], array $toppings = []): \App\Modules\Merchant\Model\MenuItem;

    /**
     * Update an existing menu item with sizes and toppings.
     *
     * @param string $itemId
     * @param array $data
     * @param array $sizes
     * @param array $toppings
     * @return \App\Modules\Merchant\Model\MenuItem
     */
    public function updateItem(string $itemId, array $data, array $sizes = [], array $toppings = []): \App\Modules\Merchant\Model\MenuItem;

    /**
     * Check if item name exists in category (excluding current item).
     *
     * @param string $merchantProfileId
     * @param string $categoryId
     * @param string $name
     * @param string|null $excludeItemId
     * @return bool
     */
    public function isNameExistsInCategory(string $merchantProfileId, string $categoryId, string $name, ?string $excludeItemId = null): bool;

    /**
     * Soft delete a menu item.
     *
     * @param string $itemId
     * @return bool
     */
    public function deleteItem(string $itemId): bool;
}
