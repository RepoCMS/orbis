<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateSite;

final readonly class UpdateSiteCommand
{
    public function __construct(
        public string $siteId,
        public string $name,
        public ?string $serviceZoneId = null,
    ) {
    }
}
