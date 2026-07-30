<?php

declare(strict_types=1);

namespace App\Client\Domain\Repositories;

use App\Client\Domain\Entities\TerminalWarranty;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;

interface TerminalWarrantyRepositoryInterface
{
    public function save(TerminalWarranty $warranty): void;

    public function findById(TerminalWarrantyIdValueObject $id): ?TerminalWarranty;

    /**
     * @return TerminalWarranty[]
     */
    public function findByTerminalId(TerminalIdValueObject $terminalId): array;

    /**
     * @return TerminalWarranty[]
     */
    public function findAll(): array;
}
