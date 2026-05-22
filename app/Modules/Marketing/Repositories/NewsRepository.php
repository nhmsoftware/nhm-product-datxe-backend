<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Marketing\Interfaces\NewsRepositoryInterface;
use App\Modules\Marketing\Model\News;
use App\Modules\Marketing\Model\Enums\MarketingItemStatus;

class NewsRepository extends BaseRepository implements NewsRepositoryInterface
{
    public function getModel(): string
    {
        return News::class;
    }

    public function getActiveNews()
    {
        return $this->model
            ->where('status', MarketingItemStatus::ACTIVE->value)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'desc')
            ->get();
    }
}
