<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Merchant\Interfaces\ComboItemRepositoryInterface;
use App\Modules\Merchant\Model\ComboItem;

final class ComboItemRepository extends BaseRepository implements ComboItemRepositoryInterface
{
    public function getModel(): string
    {
        return ComboItem::class;
    }

    public function deleteByCombo(string $comboId): void
    {
        $this->getQuery()->where('combo_id', $comboId)->delete();
    }
}
