<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Service;

use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Identity\Domain\Service\UserEmailResolverInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Adaptador cross-BC que resuelve el email de un usuario buscando en:
 *   1. Employee (Organization BC) — empleados de la empresa
 *   2. Contact (Client BC) — contactos de clientes con acceso al portal
 *
 * Vive en Infrastructure porque hace consultas a repositorios de otros BCs.
 */
final readonly class CrossBcUserEmailResolver implements UserEmailResolverInterface
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private ContactRepositoryInterface $contactRepository,
    ) {
    }

    public function resolveEmail(UserIdValueObject $userId): ?string
    {
        $employee = $this->employeeRepository->findByUserId($userId);
        if ($employee !== null) {
            return (string) $employee->email();
        }

        $contact = $this->contactRepository->findByUserId($userId);
        if ($contact !== null) {
            return (string) $contact->email();
        }

        return null;
    }
}
