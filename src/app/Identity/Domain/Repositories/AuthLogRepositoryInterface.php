<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use DateTimeImmutable;

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

    /**
     * @param UserIdValueObject|null $userId
     * @param DateTimeImmutable|null $dateFrom
     * @param DateTimeImmutable|null $dateTo
     * @param int $page
     * @param int $limit
     * @return AuthLog[]
     */
    public function findByFilters(?UserIdValueObject $userId, ?DateTimeImmutable $dateFrom, ?DateTimeImmutable $dateTo, int $page, int $limit): array;

    public function countByFilters(?UserIdValueObject $userId, ?DateTimeImmutable $dateFrom, ?DateTimeImmutable $dateTo): int;

    /**
     * Cuenta intentos de login fallidos desde una IP en los últimos N minutos.
     */
    public function countRecentFailedByIp(string $ipAddress, int $minutes): int;
}
