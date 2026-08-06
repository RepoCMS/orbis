<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RejectVacationRequest;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Events\VacationRejected;
use App\Organization\Domain\Exceptions\VacationRequestException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class RejectVacationRequestHandler
{
    public function __construct(
        private VacationRequestRepositoryInterface $vacationRequestRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(RejectVacationRequestCommand $command): void
    {
        $requestId    = new VacationRequestIdValueObject($command->requestId);
        $reviewedById = new EmployeeIdValueObject($command->reviewedBy);

        $vacationRequest = $this->vacationRequestRepository->findById($requestId)
            ?? throw new VacationRequestException('Solicitud de vacaciones no encontrada.');

        // Verificar que el revisor existe
        $reviewer = $this->employeeRepository->findById($reviewedById)
            ?? throw EmployeeNotFoundException::withId($command->reviewedBy);

        $vacationRequest->reject($reviewedById, $command->reason);

        $this->vacationRequestRepository->save($vacationRequest);

        $this->publisher->publish(new VacationRejected(
            employeeId: (string) $vacationRequest->employeeId(),
            requestId:  $command->requestId,
            reason:     $command->reason,
        ));
    }
}
