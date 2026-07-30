<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;

interface RequestRepositoryInterface
{
    public function findById(RequestIdValueObject $id): ?Request;

    public function findByReference(string $reference): ?Request;

    /** @return Request[] */
    public function findPending(): array;

    /** @return Request[] */
    public function findAll(): array;

    public function findByIdWithItems(RequestIdValueObject $id): ?Request;

    public function save(Request $request): void;
}
