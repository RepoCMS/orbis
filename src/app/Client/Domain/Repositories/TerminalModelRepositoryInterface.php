<?php

declare(strict_types=1);

namespace App\Client\Domain\Repositories;

use App\Client\Domain\Entities\TerminalModel;
use App\Client\Domain\ValueObjects\TerminalModelIdValueObject;

interface TerminalModelRepositoryInterface
{
    public function findById(TerminalModelIdValueObject $id): ?TerminalModel;

    /** @return TerminalModel[] */
    public function findAll(): array;

    public function save(TerminalModel $model): void;

    public function delete(TerminalModelIdValueObject $id): void;
}
