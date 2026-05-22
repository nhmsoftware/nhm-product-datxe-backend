<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Merchant\Model\MenuItem;
use Illuminate\Support\Collection;

interface MenuItemRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find an item by ID.
     *
     * @param string $itemId
     * @return MenuItem|null
     */
    public function findItem(string $itemId): ?MenuItem;

    /**
     * Get menu items by category.
     *
     * @param string $categoryId
     * @return Collection
     */
    public function getItemsByCategory(string $categoryId): Collection;

    /**
     * Create a new menu item with sizes and toppings.
     *
     * @param array $data
     * @param array $sizes
     * @param array $toppings
     * @return MenuItem
     */
    public function createItem(array $data, array $sizes = [], array $toppings = []): MenuItem;

    /**
     * Update an existing menu item with sizes and toppings.
     *
     * @param string $itemId
     * @param array $data
     * @param array $sizes
     * @param array $toppings
     * @return MenuItem
     */
    public function updateItem(string $itemId, array $data, array $sizes = [], array $toppings = []): MenuItem;

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

    /**
     * Update availability status of a menu item.
     *
     * @param string $itemId
     * @param bool $isAvailable
     * @return bool
     */
    public function updateItemStatus(string $itemId, bool $isAvailable): bool;

    /**
     * Update average rating and total reviews for a menu item.
     *
     * @param string $itemId
     * @param float $rating
     * @param int $totalReviews
     * @return bool
     */
    public function updateRatingStats(string $itemId, float $rating, int $totalReviews): bool;

    /**
     * Find item by category and name.
     */
    public function findItemByName(string $merchantProfileId, string $categoryId, string $name): ?MenuItem;
}
