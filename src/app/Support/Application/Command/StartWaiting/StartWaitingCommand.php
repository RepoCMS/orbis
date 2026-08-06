<?php

declare(strict_types=1);

namespace App\Support\Application\Command\StartWaiting;

final readonly class StartWaitingCommand
{
    public function __construct(
        public string $ticketId,
    ) {
    }
}
