<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Modules\Merchant\DTO\GetMenuDTO;
use Illuminate\Support\Collection;

interface MenuServiceInterface
{
    /**
     * Get the full menu for a merchant.
     *
     * @param GetMenuDTO $dto
     * @return Collection
     */
    public function getMerchantMenu(GetMenuDTO $dto): Collection;

    /**
     * Get list of categories for the authenticated merchant.
     *
     * @param \App\Modules\Merchant\DTO\GetMenuCategoriesDTO $dto
     * @return Collection
     */
    public function getMerchantCategories(\App\Modules\Merchant\DTO\GetMenuCategoriesDTO $dto): Collection;

    /**
     * Create a new menu item.
     *
     * @param \App\Modules\Merchant\DTO\CreateMenuItemDTO $dto
     * @return \App\Core\Services\ServiceReturn
     */
    public function createMenuItem(\App\Modules\Merchant\DTO\CreateMenuItemDTO $dto): \App\Core\Services\ServiceReturn;

    /**
     * Update an existing menu item.
     *
     * @param \App\Modules\Merchant\DTO\UpdateMenuItemDTO $dto
     * @return \App\Core\Services\ServiceReturn
     */
    public function updateMenuItem(\App\Modules\Merchant\DTO\UpdateMenuItemDTO $dto): \App\Core\Services\ServiceReturn;

    /**
     * Soft delete a menu item.
     *
     * @param \App\Modules\Merchant\DTO\DeleteMenuItemDTO $dto
     * @return \App\Core\Services\ServiceReturn
     */
    public function deleteMenuItem(\App\Modules\Merchant\DTO\DeleteMenuItemDTO $dto): \App\Core\Services\ServiceReturn;

    /**
     * Update availability status of a menu item.
     * UC-68 Toggle Availability
     *
     * @param string $itemId
     * @param string $merchantProfileId
     * @param bool $isAvailable
     * @return \App\Core\Services\ServiceReturn
     */
    public function updateMenuItemStatus(string $itemId, string $merchantProfileId, bool $isAvailable): \App\Core\Services\ServiceReturn;
}
