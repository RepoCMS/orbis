<?php

declare(strict_types=1);

namespace App\Client\Domain\Repositories;

use App\Client\Domain\Entities\ModelPart;
use App\Client\Domain\ValueObjects\ModelPartIdValueObject;

interface ModelPartRepositoryInterface
{
    public function findById(ModelPartIdValueObject $id): ?ModelPart;

    public function findByPartNumber(string $partNumber): ?ModelPart;

    /** @return ModelPart[] */
    public function findByModel(string $model): array;

    public function save(ModelPart $part): void;

    public function delete(ModelPartIdValueObject $id): void;
}
