<?php

declare(strict_types=1);

namespace App\Location\Domain\Entities;

use App\Location\Domain\ValueObjects\CityIdValueObject;
use App\Location\Domain\ValueObjects\ProvinceIdValueObject;

final class City
{
    private function __construct(
        private CityIdValueObject     $id,
        private ProvinceIdValueObject $provinceId,
        private string $name,
    ) {}

    public static function reconstitute(string $id, string $provinceId, string $name): self
    {
        return new self(
            id:         CityIdValueObject::fromString($id),
            provinceId: ProvinceIdValueObject::fromString($provinceId),
            name:       $name,
        );
    }

    public function id(): CityIdValueObject             { return $this->id; }
    public function provinceId(): ProvinceIdValueObject  { return $this->provinceId; }
    public function name(): string                       { return $this->name; }
}