<?php

declare(strict_types=1);

namespace App\Inventory\Application\Service;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Valida que un usuario tenga permiso para realizar una transferencia
 * desde/hacia ubicaciones específicas.
 *
 * Reglas:
 * - Origen y destino no pueden ser la misma ubicación (para todos).
 * - Técnicos: solo transfieren DESDE su mochila y NO hacia su mochila.
 * - Admin, bodeguero, laboratorista, supervisores: sin restricciones de ubicación.
 */
final readonly class TransferAuthorizationService
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    /**
     * @param string[] $sourceLocationIds IDs de ubicaciones origen de los items
     * @throws InventoryDomainException si la transferencia no está autorizada
     */
    public function authorize(string $userId, array $sourceLocationIds, string $destinationLocationId): void
    {
        foreach ($sourceLocationIds as $locId) {
            if ($locId === $destinationLocationId) {
                throw new InventoryDomainException(
                    'El origen y destino no pueden ser la misma ubicación.'
                );
            }
        }

        $employee = $this->employeeRepository->findByUserId(new UserIdValueObject($userId));

        if ($employee === null) {
            throw new InventoryDomainException('Empleado no encontrado para el usuario actual.');
        }

        if (!$employee->profile()->isTechnician()) {
            return;
        }

        $technicianBackpack = null;
        $locations = $this->locationRepository->findByEmployeeId((string) $employee->id());

        foreach ($locations as $location) {
            if ($location->isBackpack()) {
                $technicianBackpack = (string) $location->id();
                break;
            }
        }

        if ($technicianBackpack === null) {
            throw new InventoryDomainException(
                'No tienes una mochila asignada. Contacta al administrador.'
            );
        }

        if ($destinationLocationId === $technicianBackpack) {
            throw new InventoryDomainException(
                'No puedes transferir stock hacia tu propia mochila.'
            );
        }

        foreach ($sourceLocationIds as $locId) {
            if ($locId !== $technicianBackpack) {
                throw new InventoryDomainException(
                    'Como técnico solo puedes transferir stock desde tu propia mochila.'
                );
            }
        }
    }
}
