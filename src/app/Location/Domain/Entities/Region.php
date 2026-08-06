<?php

declare(strict_types=1);

namespace App\Location\Domain\Entities;

use App\Location\Domain\ValueObjects\RegionIdValueObject;

final class Region
{
    private function __construct(
        private RegionIdValueObject $id,
        private string $name,
    ) {
    }

    public static function reconstitute(string $id, string $name): self
    {
        return new self(
            id:   RegionIdValueObject::fromString($id),
            name: $name,
        );
    }

    public function id(): RegionIdValueObject
    {
        return $this->id;
    }
    public function name(): string
    {
        return $this->name;
    }
}
