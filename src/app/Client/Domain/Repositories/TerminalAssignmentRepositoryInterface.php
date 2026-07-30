<?php

declare(strict_types=1);

namespace App\Client\Domain\Repositories;

use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;

interface TerminalAssignmentRepositoryInterface
{
    public function findById(TerminalAssignmentIdValueObject $id): ?TerminalAssignment;

    /**
     * @return TerminalAssignment[]
     */
    public function findAll(): array;

    public function save(TerminalAssignment $terminalAssignment): void;
}
