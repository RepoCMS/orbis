<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;

interface QuickTemplateRepositoryInterface
{
    public function findById(QuickTemplateIdValueObject $id): ?QuickTemplate;

    /** @return QuickTemplate[] */
    public function findByModelId(string $modelId): array;

    /** @return QuickTemplate[] */
    public function findActiveByModelId(string $modelId): array;

    /** @return QuickTemplate[] */
    public function findAll(): array;

    public function save(QuickTemplate $template): void;

    public function delete(QuickTemplate $template): void;
}
