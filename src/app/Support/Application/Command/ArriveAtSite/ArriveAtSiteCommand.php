<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ArriveAtSite;

final readonly class ArriveAtSiteCommand
{
    public function __construct(
        public string $ticketId,
    ) {}
}
