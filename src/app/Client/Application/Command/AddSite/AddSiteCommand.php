<?php

declare(strict_types=1);

namespace App\Client\Application\Command\AddSite;

final readonly class AddSiteCommand
{
    public function __construct(
        public string $clientId,
        public string $cityId,
        public string $terminalId,
        public string $name,
        public ?string $serviceZoneId = null,
    ) {
    }
}
