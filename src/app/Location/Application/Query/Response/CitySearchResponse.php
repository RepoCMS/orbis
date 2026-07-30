<?php

declare(strict_types=1);

namespace App\Location\Application\Query\Response;

use App\Location\Domain\Entities\City;

final readonly class CitySearchResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $provinceId,
        public string $provinceName,
    ) {}

    public static function fromEntity(City $city): self
    {
        return new self(
            id:           (string) $city->id(),
            name:         $city->name(),
            provinceId:   (string) $city->provinceId(),
            provinceName: '', // resolved by handler
        );
    }
}
