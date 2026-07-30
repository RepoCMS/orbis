<?php

declare(strict_types=1);

namespace App\Inventory\Application\Service;

use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Resuelve si un usuario técnico tiene mochila asignada para scoping
 * de ubicaciones en el formulario de solicitud de repuestos.
 *
 * Regla: Técnicos de campo solo pueden solicitar repuestos hacia su propia mochila
 * y no pueden usar su propia mochila como origen.
 * Otros perfiles (admin, bodeguero, laboratorista, etc.) no tienen restricciones.
 *
 * Este es un Application Service que orquesta entre Organization BC (empleado)
 * e Inventory BC (ubicación/mochila). Respeta DDD: solo usa IDs entre BCs.
 */
final readonly class LocationRequestScopeResolver
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    /**
     * Resuelve el ID de la mochila del técnico autenticado.
     *
     * @param string|null $userId ULID del usuario autenticado, o null
     *
     * @return string|null Location ID de la mochila, o null si no hay restricción
     *                     (no es técnico, no tiene mochila, o userId inválido)
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

        $locations = $this->locationRepository->findByEmployeeId((string) $employee->id());

        foreach ($locations as $location) {
            if ($location->isBackpack()) {
                return (string) $location->id();
            }
        }

        return null;
    }
}
