<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CompleteAssistance;

final readonly class CompleteAssistanceCommand
{
    public function __construct(
        public string $ticketId,
    ) {
    }
}
