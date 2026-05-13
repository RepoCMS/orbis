<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Domain;

interface AtmComponentRepositoryInterface
{
    /** @return AtmComponent[] */
    public function findByParent(?AtmComponentId $parentId): array;

    /** @return AtmComponent[] */
    public function findAllActive(): array;

    public function findById(AtmComponentId $id): ?AtmComponent;
}
