<?php

declare(strict_types=1);

namespace App\Modules\Communication\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Communication\Events\ChatMessageSent;
use App\Modules\Communication\Interfaces\ChatMessageRepositoryInterface;
use App\Modules\Communication\Interfaces\CommunicationServiceInterface;
use App\Modules\Communication\Listeners\NotifyRealtimeOnChatMessageSent;
use App\Modules\Communication\Repositories\ChatMessageRepository;
use App\Modules\Communication\Services\CommunicationService;
use Illuminate\Support\Facades\Event;

final class CommunicationServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Communication';
    }

    public function register(): void
    {
        $this->app->singleton(ChatMessageRepositoryInterface::class, ChatMessageRepository::class);
        $this->app->singleton(CommunicationServiceInterface::class, CommunicationService::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Đăng ký Event & Listener
        Event::listen(
            ChatMessageSent::class,
            NotifyRealtimeOnChatMessageSent::class
        );
    }
}
