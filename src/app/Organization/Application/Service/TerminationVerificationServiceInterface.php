<?php

declare(strict_types=1);

namespace App\Organization\Application\Service;

use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

interface TerminationVerificationServiceInterface
{
    /**
     * Verifica si hay items pendientes que bloqueen la terminación.
     *
     * @return array<string, array<array{type: string, description: string}>> Items pendientes agrupados por BC
     */
    public function findBlockingItems(EmployeeIdValueObject $employeeId): array;
}
