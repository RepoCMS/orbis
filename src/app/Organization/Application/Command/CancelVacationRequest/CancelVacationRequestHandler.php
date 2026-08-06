<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CancelVacationRequest;

use App\Organization\Domain\Events\VacationCancelled;
use App\Organization\Domain\Exceptions\VacationRequestException;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class CancelVacationRequestHandler
{
    public function __construct(
        private VacationRequestRepositoryInterface $vacationRequestRepository,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(CancelVacationRequestCommand $command): void
    {
        $requestId  = new VacationRequestIdValueObject($command->requestId);
        $employeeId = new EmployeeIdValueObject($command->employeeId);

        $vacationRequest = $this->vacationRequestRepository->findById($requestId)
            ?? throw new VacationRequestException('Solicitud de vacaciones no encontrada.');

        // Validar que la solicitud pertenece al empleado
        if ((string) $vacationRequest->employeeId() !== $command->employeeId) {
            throw new VacationRequestException(
                'No puedes cancelar una solicitud que no te pertenece.'
            );
        }

        $vacationRequest->cancel();

        $this->vacationRequestRepository->save($vacationRequest);

        $this->publisher->publish(new VacationCancelled(
            employeeId: $command->employeeId,
            requestId:  $command->requestId,
        ));
    }
}
