<?php

declare(strict_types=1);

namespace App\Modules\Communication\Events;

use App\Modules\Communication\Model\ChatMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ChatMessageSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ChatMessage $message
    ) {
    }
}
