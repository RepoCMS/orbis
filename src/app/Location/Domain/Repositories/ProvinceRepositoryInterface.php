<?php

declare(strict_types=1);

namespace App\Location\Domain\Repositories;

use App\Location\Domain\Entities\Province;
use App\Location\Domain\ValueObjects\ProvinceIdValueObject;
use App\Location\Domain\ValueObjects\RegionIdValueObject;

interface ProvinceRepositoryInterface
{
    public function findById(ProvinceIdValueObject $id): ?Province;

    public function findByName(string $name): ?Province;

    /**
     * @return Province[]
     */
    public function findAll(): array;

    /**
     * @return Province[]
     */
    public function findByRegion(RegionIdValueObject $regionId): array;

    public function save(Province $province): void;

    public function delete(ProvinceIdValueObject $id): void;
}
