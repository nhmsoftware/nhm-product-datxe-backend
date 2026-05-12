<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Complaint\Interfaces\ComplaintRepositoryInterface;
use App\Modules\Complaint\Interfaces\ComplaintServiceInterface;
use App\Modules\Complaint\Repositories\ComplaintRepository;
use App\Modules\Complaint\Services\ComplaintService;

final class ComplaintServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleName(): string
    {
        return 'Complaint';
    }

    public function boot(): void
    {
        parent::boot();
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Complaint\Events\ComplaintHandled::class,
            \App\Modules\Complaint\Listeners\NotifyRealtimeOnComplaintHandled::class
        );
    }

    public function register(): void
    {
        $this->app->singleton(ComplaintRepositoryInterface::class, ComplaintRepository::class);
        $this->app->singleton(ComplaintServiceInterface::class, ComplaintService::class);
    }
}
