<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface NewsRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get active news ordered by their 'order' column
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveNews();
}
