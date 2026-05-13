<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ReactivateSite;

final readonly class ReactivateSiteCommand
{
    public function __construct(
        public string $siteId,
    ) {
    }
}
