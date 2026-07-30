<?php

declare(strict_types=1);

namespace App\Location\Domain\Repositories;

use App\Location\Domain\Entities\City;
use App\Location\Domain\ValueObjects\CityIdValueObject;
use App\Location\Domain\ValueObjects\ProvinceIdValueObject;

interface CityRepositoryInterface
{
    public function findById(CityIdValueObject $id): ?City;

    public function findByName(string $name): ?City;

    /**
     * @return City[]
     */
    public function findAll(): array;

    /**
     * @return City[]
     */
    public function findByProvince(ProvinceIdValueObject $provinceId): array;

    /**
     * @return City[]
     */
    public function searchByName(string $term, int $limit = 20): array;

    public function save(City $city): void;

    public function delete(CityIdValueObject $id): void;
}
