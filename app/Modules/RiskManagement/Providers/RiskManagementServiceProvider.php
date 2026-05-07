<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\RiskManagement\Interfaces\AntiFraudServiceInterface;
use App\Modules\RiskManagement\Interfaces\FraudAlertRepositoryInterface;
use App\Modules\RiskManagement\Interfaces\PenaltyRuleRepositoryInterface;
use App\Modules\RiskManagement\Interfaces\PenaltyRuleServiceInterface;
use App\Modules\RiskManagement\Repositories\FraudAlertRepository;
use App\Modules\RiskManagement\Repositories\PenaltyRuleRepository;
use App\Modules\RiskManagement\Services\AntiFraudService;
use App\Modules\RiskManagement\Services\PenaltyRuleService;
use App\Modules\RiskManagement\Interfaces\CancellationConfigRepositoryInterface;
use App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface;
use App\Modules\RiskManagement\Repositories\CancellationConfigRepository;
use App\Modules\RiskManagement\Services\CancellationConfigService;

/**
 * Service Provider quản lý module RiskManagement.
 */
final class RiskManagementServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'RiskManagement';
    }

    public function register(): void
    {
        // ── Repositories ──
        $this->app->singleton(FraudAlertRepositoryInterface::class, FraudAlertRepository::class);
        $this->app->singleton(PenaltyRuleRepositoryInterface::class, PenaltyRuleRepository::class);
        $this->app->singleton(CancellationConfigRepositoryInterface::class, CancellationConfigRepository::class);

        // ── Services ──────
        $this->app->singleton(AntiFraudServiceInterface::class, AntiFraudService::class);
        $this->app->singleton(PenaltyRuleServiceInterface::class, PenaltyRuleService::class);
        $this->app->singleton(CancellationConfigServiceInterface::class, CancellationConfigService::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
