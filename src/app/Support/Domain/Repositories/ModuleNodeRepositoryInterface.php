<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\AtmModel\AtmModel;
use App\Support\Domain\Entities\ModuleNode;
use App\Support\Domain\ValueObjects\ModuleNodeIdValueObject;

interface ModuleNodeRepositoryInterface
{
    public function findById(ModuleNodeIdValueObject $id): ?ModuleNode;

    public function findByCodeAndModel(string $code, AtmModel $model): ?ModuleNode;

    /** @return ModuleNode[] */
    public function findByModel(AtmModel $model): array;

    /** @return ModuleNode[] */
    public function findRootsByModel(AtmModel $model): array;

    /** @return ModuleNode[] */
    public function findChildrenByParentId(ModuleNodeIdValueObject $parentId): array;

    public function getNextPosition(?ModuleNodeIdValueObject $parentId, AtmModel $model): int;

    public function save(ModuleNode $node): void;

    public function delete(ModuleNode $node): void;
}
