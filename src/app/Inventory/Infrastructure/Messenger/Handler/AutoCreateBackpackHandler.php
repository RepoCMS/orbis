<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Messenger\Handler;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Events\EmployeeCreated;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asíncrono que crea una ubicación tipo BACKPACK (mochila) para
 * empleados con perfil técnico (technician_field o supervisor_technician).
 *
 * Escucha DomainEventMessage a través de Messenger async y filtra
 * por EmployeeCreated con perfil de técnico.
 */
#[AsMessageHandler]
final readonly class AutoCreateBackpackHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private LocationRepositoryInterface $locationRepository,
        private IdGeneratorInterface $idGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DomainEventMessage $message): void
    {
        if ($message->eventClass !== EmployeeCreated::class) {
            return;
        }

        /** @var EmployeeCreated $event */
        $event = $message->restoreEvent();

        $profile = EmployeeProfile::tryFrom($event->profile);

        if ($profile === null || !$profile->isTechnician()) {
            return;
        }

        // Business rule: un empleado solo puede tener una ubicación asignada.
        // Si ya tiene alguna (mochila, almacén, etc.), se omite la creación.
        $existingLocations = $this->locationRepository->findByEmployeeId($event->employeeId);

        if (!empty($existingLocations)) {
            return;
        }

        $locationId = $this->idGenerator->generate();
        $backpackName = sprintf('Mochila - %s %s', $event->firstName, $event->lastName);

        $backpack = Location::create(
            id: new LocationIdValueObject($locationId),
            name: new LocationNameValueObject($backpackName),
            city: '',
            address: '',
            type: LocationType::BACKPACK,
            employeeId: $event->employeeId,
        );

        try {
            $this->wrapDbExceptions(function () use ($backpack): void {
                $this->locationRepository->save($backpack);
            });
        } catch (\Throwable $e) {
            $this->logger->error('Failed to auto-create backpack: ' . $e->getMessage());
        }
    }
}
