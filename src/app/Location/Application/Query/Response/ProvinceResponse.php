<?php

declare(strict_types=1);

namespace App\Location\Application\Query\Response;

use App\Location\Domain\Entities\Province;

final readonly class ProvinceResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $regionId,
    ) {}

    public static function fromEntity(Province $province): self
    {
        return new self(
            id:       (string) $province->id(),
            name:     $province->name(),
            regionId: (string) $province->regionId(),
        );
    }
}
