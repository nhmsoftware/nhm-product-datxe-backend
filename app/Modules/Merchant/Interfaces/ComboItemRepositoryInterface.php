<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface ComboItemRepositoryInterface extends BaseRepositoryInterface
{
    public function deleteByCombo(string $comboId): void;
}
