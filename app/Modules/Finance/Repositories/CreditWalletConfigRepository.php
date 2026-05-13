<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\CreditWalletConfigRepositoryInterface;
use App\Modules\Finance\Model\CreditWalletConfig;

final class CreditWalletConfigRepository extends BaseRepository implements CreditWalletConfigRepositoryInterface
{
    public function getModel(): string
    {
        return CreditWalletConfig::class;
    }

    public function getLatestConfig(): CreditWalletConfig
    {
        /** @var CreditWalletConfig */
        return $this->model->first() ?? $this->model->create([
            'min_balance' => 50000,
            'auto_lock' => true,
            'commission_rule' => 'Default rule'
        ]);
    }

    public function updateConfig(array $data): bool
    {
        $config = $this->getLatestConfig();
        return $config->update($data);
    }
}
