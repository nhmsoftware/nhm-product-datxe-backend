<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Merchant\Interfaces\ComboRepositoryInterface;
use App\Modules\Merchant\Model\Combo;
use Illuminate\Database\Eloquent\Collection;

final class ComboRepository extends BaseRepository implements ComboRepositoryInterface
{
    public function getModel(): string
    {
        return Combo::class;
    }

    public function getByMerchant(string $merchantProfileId): Collection
    {
        return $this->getQuery()->where('merchant_profile_id', $merchantProfileId)
            ->withCount('items')
            ->orderBy('order')
            ->get();
    }

    public function findWithDetails(string $comboId): ?Combo
    {
        /** @var Combo|null */
        return $this->getQuery()->where('id', $comboId)
            ->with(['items.menuItem'])
            ->first();
    }

    public function findWithTrashed(string $comboId): ?Combo
    {
        return $this->getQuery()->withTrashed()->where('id', $comboId)->first();
    }
}
