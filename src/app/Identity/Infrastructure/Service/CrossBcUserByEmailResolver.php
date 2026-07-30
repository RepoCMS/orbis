<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Service;

use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Identity\Domain\Service\UserByEmailResolverInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Adaptador cross-BC que resuelve el userId de un usuario a partir de su email
 * buscando en:
 *   1. employee (Organization BC) — empleados de la empresa
 *   2. contact (Client BC) — contactos de clientes con acceso al portal
 *
 * Ahora usa employee->userId() y contact->userId() directamente.
 *
 * Vive en Infrastructure porque consulta repositorios de otros BCs.
 */
final readonly class CrossBcUserByEmailResolver implements UserByEmailResolverInterface
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private ContactRepositoryInterface  $contactRepository,
    ) {
    }

    public function resolveByEmail(string $email): ?UserIdValueObject
    {
        $employee = $this->employeeRepository->findByEmail($email);
        if ($employee !== null && $employee->userId() !== null) {
            return new UserIdValueObject($employee->userId());
        }

        $contact = $this->contactRepository->findByEmail($email);
        if ($contact !== null && $contact->userId() !== null) {
            return new UserIdValueObject($contact->userId());
        }

        return null;
    }
}
