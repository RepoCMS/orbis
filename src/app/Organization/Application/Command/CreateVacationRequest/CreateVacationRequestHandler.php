<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateVacationRequest;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\VacationRequest;
use App\Organization\Domain\Events\VacationRequested;
use App\Organization\Domain\Exceptions\VacationRequestException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;

final readonly class CreateVacationRequestHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private VacationRequestRepositoryInterface $vacationRequestRepository,
        private EmployeeSpecialPeriodRepositoryInterface $periodRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $publisher,
    ) {}

    public function __invoke(CreateVacationRequestCommand $command): string
    {
        $employeeId = new EmployeeIdValueObject($command->employeeId);
        $employee   = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($command->employeeId);

        if (!$employee->status()->isEmployed()) {
            throw new VacationRequestException(
                'No se puede solicitar vacaciones para un empleado inactivo.'
            );
        }

        $startDate = new DateTimeImmutable($command->startDate);
        $endDate   = new DateTimeImmutable($command->endDate);

        // Validar que no existan períodos activos (SCHEDULED o IN_PROGRESS) que se solapen
        $overlapping = $this->periodRepository->findOverlappingByEmployee($employeeId, $startDate, $endDate);
        if (count($overlapping) > 0) {
            throw new VacationRequestException(
                'El empleado ya tiene un período activo que se solapa con las fechas indicadas.'
            );
        }

        // Validar que no exista una solicitud PENDING previa con el mismo rango
        $pendingRequests = $this->vacationRequestRepository->findPendingByEmployee($employeeId);
        foreach ($pendingRequests as $pending) {
            if ($pending->startDate() < $endDate && $pending->endDate() > $startDate) {
                throw new VacationRequestException(
                    'El empleado ya tiene una solicitud de vacaciones pendiente que se solapa con las fechas indicadas.'
                );
            }
        }

        $id = new VacationRequestIdValueObject($this->idGenerator->generate());

        $vacationRequest = VacationRequest::request(
            id:         $id,
            employeeId: $employeeId,
            startDate:  $startDate,
            endDate:    $endDate,
        );

        $this->vacationRequestRepository->save($vacationRequest);

        $this->publisher->publish(new VacationRequested(
            employeeId: $command->employeeId,
            startDate:  $command->startDate,
            endDate:    $command->endDate,
        ));

        return (string) $id;
    }
}
