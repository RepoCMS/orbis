<?php

declare(strict_types=1);

namespace App\Inventory\Application\Service;

use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Resuelve si la consulta de stock debe limitarse a una ubicación específica
 * según el perfil del usuario autenticado.
 *
 * Regla: Técnicos de campo y supervisores técnicos solo ven stock de su mochila.
 * Otros perfiles (admin, bodeguero, laboratorista, etc.) ven todo el stock.
 *
 * Este es un Application Service que orquesta entre Organization BC (empleado)
 * e Inventory BC (ubicación/mochila). Respeta DDD: solo usa IDs entre BCs.
 */
final readonly class StockViewScopeResolver
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    /**
     * @param string $userId ULID del usuario autenticado
     * @return string|null Location ID para scope, o null si no hay restricción
     */
    public function resolve(string $userId): ?string
    {
        $employee = $this->employeeRepository->findByUserId($userId);

        if ($employee === null || !$employee->profile()->isTechnician()) {
            return null;
        }

        $locations = $this->locationRepository->findByEmployeeId((string) $employee->id());

        foreach ($locations as $location) {
            if ($location->isBackpack()) {
                return (string) $location->id();
            }
        }

        return null;
    }
}
