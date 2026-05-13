<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateSite;

final readonly class DeactivateSiteCommand
{
    public function __construct(
        public string $siteId,
    ) {
    }
}
