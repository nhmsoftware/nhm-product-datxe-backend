<?php

declare(strict_types=1);

namespace App\Modules\Chauffeur\Providers;

use App\Modules\Chauffeur\Interfaces\ChauffeurRepositoryInterface;
use App\Modules\Chauffeur\Interfaces\ChauffeurServiceInterface;
use App\Modules\Chauffeur\Repositories\ChauffeurRepository;
use App\Modules\Chauffeur\Services\ChauffeurService;
use Illuminate\Support\ServiceProvider;

class ChauffeurServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ChauffeurRepositoryInterface::class, ChauffeurRepository::class);
        $this->app->bind(ChauffeurServiceInterface::class, ChauffeurService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
