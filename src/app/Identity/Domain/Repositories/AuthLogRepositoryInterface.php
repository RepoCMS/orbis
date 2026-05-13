<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

interface AuthLogRepositoryInterface
{
    /**
     * Persiste un registro de auditoría. Los registros son inmutables — nunca se actualizan.
     */
    public function append(AuthLog $authLog): void;

    /**
     * Lista los registros de un usuario, ordenados por occurredAt DESC, con paginación.
     *
     * @return AuthLog[]
     */
    public function findByUserId(UserIdValueObject $userId, int $limit, int $offset): array;

    public function countByUserId(UserIdValueObject $userId): int;
}
