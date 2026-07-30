<?php

declare(strict_types=1);

namespace App\Support\Application\Service;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Resuelve si la consulta de repuestos pendientes debe limitarse
 * según el perfil del usuario autenticado.
 *
 * Regla: Técnicos de campo solo ven/solicitan repuestos de los tickets
 * a los que están asignados. El locationId permite reservar desde su mochila.
 * Otros perfiles (admin, coordinador, etc.) no tienen restricción.
 */
final readonly class SparePartViewScopeResolver
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private LocationRepositoryInterface $locationRepository,
    ) {}

    public function resolve(string $userId): ?SparePartScope
    {
        $employee = $this->employeeRepository->findByUserId(new UserIdValueObject($userId));

        if ($employee === null || !$employee->profile()->isTechnician()) {
            return null;
        }

        $employeeId = (string) $employee->id();
        $locationId = null;

        $locations = $this->locationRepository->findByEmployeeId($employeeId);
        foreach ($locations as $location) {
            if ($location->isBackpack()) {
                $locationId = (string) $location->id();
                break;
            }
        }

        return new SparePartScope(
            employeeId: $employeeId,
            locationId: $locationId,
        );
    }
}
