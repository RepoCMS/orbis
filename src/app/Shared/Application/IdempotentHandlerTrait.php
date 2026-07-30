<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\Shared\Exceptions\ApplicationException;

trait IdempotentHandlerTrait
{
    /**
     * Check idempotency before executing the command.
     * Returns true if execution should proceed, false if already processed.
     *
     * @param string $idempotencyKey Unique key for this command invocation
     * @param string $commandType   Class name of the command
     * @param string $userId        ID of the user executing the command
     *
     * @throws ApplicationException If the command was already processed
     */
    protected function ensureUnique(string $idempotencyKey, string $commandType, string $userId): void
    {
        if (!$this->idempotencyService->tryProcess($commandType, $idempotencyKey, $userId)) {
            throw new ApplicationException(
                'Esta operación ya fue procesada. No se puede ejecutar nuevamente.',
            );
        }
    }
}
