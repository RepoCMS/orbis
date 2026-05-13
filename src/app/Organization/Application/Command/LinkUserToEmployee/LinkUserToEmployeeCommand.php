<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\LinkUserToEmployee;

/**
 * Asigna un User (Identity BC) existente a un Employee que aún no tiene usuario.
 */
final readonly class LinkUserToEmployeeCommand
{
    public function __construct(
        public string $employeeId,
        public string $userId,
    ) {
    }
}
