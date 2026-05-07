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
}
