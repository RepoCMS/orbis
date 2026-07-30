<?php

declare(strict_types=1);

namespace App\Shared\Application\Service;

interface IdempotencyServiceInterface
{
    public function tryProcess(string $commandType, string $idempotencyKey, string $userId): bool;
}
