<?php

declare(strict_types=1);

namespace App\Shared\Application\Service;

use App\Shared\Domain\Entities\ProcessedCommand;
use App\Shared\Domain\Repositories\ProcessedCommandRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Domain\ValueObjects\ProcessedCommandId;

final readonly class IdempotencyService implements IdempotencyServiceInterface
{
    public function __construct(
        private ProcessedCommandRepositoryInterface $repository,
        private IdGeneratorInterface $idGenerator,
    ) {}

    /**
     * Try to mark a command as processed. Returns true if this is the first time
     * (execution should proceed), false if already processed (duplicate).
     *
     * Uses a DB-level unique constraint (handled by the repository) to prevent
     * race conditions.
     */
    public function tryProcess(string $commandType, string $idempotencyKey, string $userId): bool
    {
        if ($this->repository->exists($commandType, $idempotencyKey)) {
            return false;
        }

        return $this->repository->trySave(
            ProcessedCommand::register(
                id: new ProcessedCommandId($this->idGenerator->generate()),
                commandType: $commandType,
                idempotencyKey: $idempotencyKey,
                processedBy: $userId,
            ),
        );
    }

    /**
     * Build a deterministic idempotency key from command class + signature.
     * The caller must provide a payload hash (e.g. sha256 of serialized command data).
     */
    public static function buildKey(string $commandClass, string $payloadHash): string
    {
        return substr(hash('sha256', $commandClass . '::' . $payloadHash), 0, 64);
    }
}
