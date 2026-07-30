<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Service;

use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Notification\Domain\Services\ContactResolverInterface;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

/**
 * Resuelve datos de contacto para RecipientType::LOCATION.
 *
 * Para un locationId dado, busca la ubicación en Inventory BC, obtiene
 * el employeeId responsable, y resuelve su email/teléfono desde Organization BC.
 */
final readonly class LocationContactResolver implements ContactResolverInterface
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {}

    public function resolve(string $recipientType, string $recipientId): array
    {
        if ($recipientType !== 'location') {
            return ['email' => null, 'phone' => null, 'name' => null];
        }

        $location = $this->locationRepository->findById(
            LocationIdValueObject::fromString($recipientId),
        );

        if ($location === null || $location->employeeId() === null) {
            return ['email' => null, 'phone' => null, 'name' => null];
        }

        $employee = $this->employeeRepository->findById(
            EmployeeIdValueObject::fromString($location->employeeId()),
        );

        if ($employee === null) {
            return ['email' => null, 'phone' => null, 'name' => null];
        }

        return [
            'email' => (string) $employee->email(),
            'phone' => $employee->phoneNumber(),
            'name' => $employee->fullName(),
        ];
    }
}
