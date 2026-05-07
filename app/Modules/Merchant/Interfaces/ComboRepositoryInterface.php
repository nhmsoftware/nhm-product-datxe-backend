<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Merchant\Model\Combo;

interface ComboRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all combos of a merchant
     * UC-61 Manage Combo
     */
    public function getByMerchant(string $merchantProfileId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Find a combo with items and menu item details
     * UC-53 View Combo Detail
     */
    public function findWithDetails(string $comboId): ?Combo;

    /**
     * Find a combo including trashed ones
     */
    public function findWithTrashed(string $comboId): ?Combo;
}
