<?php

declare(strict_types=1);

namespace App\Support\Application\Command\StartAssistanceFromWaiting;

final readonly class StartAssistanceFromWaitingCommand
{
    public function __construct(
        public string $ticketId,
    ) {}
}
