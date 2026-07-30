<?php

declare(strict_types=1);

namespace App\Inventory\Application\Service;

use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Resuelve si la lista de movimientos debe filtrarse por usuario
 * según el perfil del usuario autenticado.
 *
 * Regla: Técnicos de campo solo ven movimientos donde son creador o destinatario.
 * Otros perfiles (admin, bodeguero, laboratorista, etc.) ven todo.
 */
final readonly class MovementViewScopeResolver implements MovementScopeResolverInterface
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    /**
     * @param string $userId ULID del usuario autenticado
     * @return string|null UserId para filtrar, o null si no hay restricción
     */
    public function resolve(?string $userId): ?string
    {
        if ($userId === null || $userId === '') {
            return null;
        }

        $employee = $this->employeeRepository->findByUserId($userId);

        if ($employee === null || !$employee->profile()->isTechnician()) {
            return null;
        }

        return $userId;
    }
}
