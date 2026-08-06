<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ApproveVacationRequest;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\EmployeeSpecialPeriod;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Events\VacationApproved;
use App\Organization\Domain\Exceptions\VacationRequestException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ApproveVacationRequestHandler
{
    public function __construct(
        private VacationRequestRepositoryInterface $vacationRequestRepository,
        private EmployeeSpecialPeriodRepositoryInterface $periodRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(ApproveVacationRequestCommand $command): void
    {
        $requestId    = new VacationRequestIdValueObject($command->requestId);
        $reviewedById = new EmployeeIdValueObject($command->reviewedBy);

        $vacationRequest = $this->vacationRequestRepository->findById($requestId)
            ?? throw new VacationRequestException('Solicitud de vacaciones no encontrada.');

        // Verificar que el revisor existe
        $reviewer = $this->employeeRepository->findById($reviewedById)
            ?? throw EmployeeNotFoundException::withId($command->reviewedBy);

        $vacationRequest->approve($reviewedById);

        // Crear el EmployeeSpecialPeriod real
        $periodId = new EmployeeSpecialPeriodIdValueObject($this->idGenerator->generate());

        $period = EmployeeSpecialPeriod::schedule(
            id:         $periodId,
            employeeId: $vacationRequest->employeeId(),
            type:       EmployeeSpecialPeriodType::VACATION,
            startedAt:  $vacationRequest->startDate(),
            endedAt:    $vacationRequest->endDate(),
        );

        $this->vacationRequestRepository->save($vacationRequest);
        $this->periodRepository->save($period);

        $this->publisher->publish(new VacationApproved(
            employeeId: (string) $vacationRequest->employeeId(),
            requestId:  $command->requestId,
            startDate:  $vacationRequest->startDate()->format('Y-m-d'),
            endDate:    $vacationRequest->endDate()->format('Y-m-d'),
        ));
    }
}
