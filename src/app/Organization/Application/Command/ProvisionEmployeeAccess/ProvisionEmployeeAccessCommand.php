<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ProvisionEmployeeAccess;

/**
 * Crea una cuenta de usuario en Identity BC y la vincula al empleado.
 * Orquesta el flujo cross-BC de manera que el Controller no dependa de Identity.
 */
final readonly class ProvisionEmployeeAccessCommand
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $username,
        public readonly string $rawPassword,
        public readonly string $roleId,
    ) {
    }
}
