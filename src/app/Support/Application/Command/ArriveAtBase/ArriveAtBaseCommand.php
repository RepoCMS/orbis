<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ArriveAtBase;

final readonly class ArriveAtBaseCommand
{
    public function __construct(
        public string $ticketId,
    ) {
    }
}
