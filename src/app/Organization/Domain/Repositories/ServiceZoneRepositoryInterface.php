<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repositories;

use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

interface ServiceZoneRepositoryInterface
{
    public function findById(ServiceZoneIdValueObject $id): ?ServiceZone;

    /** @return ServiceZone[] */
    public function findAllActive(): array;

    /** @return ServiceZone[] */
    public function findAll(): array;

    public function existsByName(string $name, ?ServiceZoneIdValueObject $excludeId = null): bool;

    public function save(ServiceZone $zone): void;
}
