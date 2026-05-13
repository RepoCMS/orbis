<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\Response;

use App\Organization\Domain\Entities\ServiceZone;

final readonly class ServiceZoneResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public array $cityIds,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }

    public static function fromEntity(ServiceZone $zone): self
    {
        return new self(
            id:        (string) $zone->id(),
            name:      $zone->name(),
            status:    $zone->status()->value,
            cityIds:   $zone->cityIds(),
            createdAt: $zone->createdAt()->format('Y-m-d H:i:s'),
            updatedAt: $zone->updatedAt()?->format('Y-m-d H:i:s'),
        );
    }
}
