<?php

declare(strict_types=1);

namespace App\Location\Domain\Repositories;

use App\Location\Domain\Entities\Region;
use App\Location\Domain\ValueObjects\RegionIdValueObject;

interface RegionRepositoryInterface
{
    public function findById(RegionIdValueObject $id): ?Region;

    public function findByName(string $name): ?Region;

    /**
     * @return Region[]
     */
    public function findAll(): array;

    public function save(Region $region): void;

    public function delete(RegionIdValueObject $id): void;
}
