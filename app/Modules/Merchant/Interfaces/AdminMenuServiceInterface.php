<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\AdminCreateMenuItemDTO;
use App\Modules\Merchant\DTO\AdminUpdateMenuItemDTO;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;

interface AdminMenuServiceInterface
{
    /**
     * Get the full menu (categories and items) for a merchant.
     */
    public function getMerchantMenu(string $merchantProfileId): Collection;

    /**
     * Get categories only for a merchant (admin use).
     *
     * @param string $merchantProfileId
     * @return Collection
     */
    public function getMerchantCategories(string $merchantProfileId): Collection;

    /**
     * Create a menu item as an admin, logging the action.
     */
    public function createMenuItem(AdminCreateMenuItemDTO $dto): ServiceReturn;

    /**
     * Update a menu item as an admin, logging the action.
     */
    public function updateMenuItem(AdminUpdateMenuItemDTO $dto): ServiceReturn;

    /**
     * Delete a menu item as an admin, logging the action.
     */
    public function deleteMenuItem(string $itemId, string $merchantProfileId, string $actorId): ServiceReturn;

    /**
     * Update the availability status of a menu item, logging the action.
     */
    public function updateMenuItemStatus(string $itemId, string $merchantProfileId, bool $isAvailable, string $actorId): ServiceReturn;

    /**
     * Get the edit logs for a merchant's menu.
     */
    public function getEditLogs(string $merchantProfileId): ServiceReturn;

    /**
     * Export the CSV template for menu imports.
     */
    public function exportTemplate(): string;

    /**
     * Import a menu from a CSV file, logging the action.
     */
    public function importMenu(string $merchantProfileId, UploadedFile $file, string $actorId): ServiceReturn;
}
