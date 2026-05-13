<?php

declare(strict_types=1);

namespace App\Client\Application\Query\Response;

use App\Client\Domain\Entities\Site;

final readonly class SiteResponse
{
    public function __construct(
        public string $id,
        public string $clientId,
        public string $cityId,
        public string $terminalId,
        public string $name,
        public string $status,
        public ?string $serviceZoneId,
    ) {
    }

    public static function fromEntity(Site $site): self
    {
        return new self(
            id:            (string) $site->id(),
            clientId:      (string) $site->clientId(),
            cityId:        $site->cityId(),
            terminalId:    (string) $site->terminalId(),
            name:          $site->name(),
            status:        $site->status()->value,
            serviceZoneId: $site->serviceZoneId() !== null ? (string) $site->serviceZoneId() : null,
        );
    }
}
