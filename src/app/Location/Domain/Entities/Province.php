<?php

declare(strict_types=1);

namespace App\Location\Domain\Entities;

use App\Location\Domain\ValueObjects\ProvinceIdValueObject;
use App\Location\Domain\ValueObjects\RegionIdValueObject;

final class Province
{
    private function __construct(
        private ProvinceIdValueObject $id,
        private RegionIdValueObject   $regionId,
        private string $name,
    ) {}

    public static function reconstitute(string $id, string $regionId, string $name): self
    {
        return new self(
            id:       ProvinceIdValueObject::fromString($id),
            regionId: RegionIdValueObject::fromString($regionId),
            name:     $name,
        );
    }

    public function id(): ProvinceIdValueObject     { return $this->id; }
    public function regionId(): RegionIdValueObject  { return $this->regionId; }
    public function name(): string                   { return $this->name; }
}