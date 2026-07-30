<?php

declare(strict_types=1);

namespace App\Shared\Domain\Repositories;

use App\Shared\Domain\Entities\ProcessedCommand;

interface ProcessedCommandRepositoryInterface
{
    public function exists(string $commandType, string $idempotencyKey): bool;

    public function save(ProcessedCommand $processedCommand): void;

    /**
     * Attempt to save a ProcessedCommand, returning false if a unique constraint
     * violation occurs (race condition / duplicate). This moves DB-specific
     * exception handling to the Infrastructure layer.
     */
    public function trySave(ProcessedCommand $processedCommand): bool;
}
