<?php

declare(strict_types=1);

namespace App\Location\Application\Query\Response;

use App\Location\Domain\Entities\Region;

final readonly class RegionResponse
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }

    public static function fromEntity(Region $region): self
    {
        return new self(
            id:   (string) $region->id(),
            name: $region->name(),
        );
    }
}
