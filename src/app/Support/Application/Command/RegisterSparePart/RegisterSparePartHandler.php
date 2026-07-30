<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RegisterSparePart;

use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Application\Service\SparePartViewScopeResolver;
use App\Support\Domain\Entities\TicketSparePart;
use App\Support\Domain\Exceptions\EmployeeNotAuthorizedException;
use App\Support\Domain\Repositories\TicketAssignmentRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\Repositories\TicketSparePartRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketSparePartIdValueObject;

final readonly class RegisterSparePartHandler
{
    public function __construct(
        private TicketRepositoryInterface            $ticketRepo,
        private TicketSparePartRepositoryInterface   $sparePartRepo,
        private TicketAssignmentRepositoryInterface  $assignmentRepo,
        private SparePartViewScopeResolver           $scopeResolver,
        private EmployeeRepositoryInterface          $employeeRepository,
        private IdGeneratorInterface                 $idGenerator,
    ) {}

    public function __invoke(RegisterSparePartCommand $command): void
    {
        $ticketId = TicketIdValueObject::fromString($command->ticketId);

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw TicketNotFoundException::withId($command->ticketId);
        }

        $scope = $this->scopeResolver->resolve($command->userId);

        if ($scope !== null && $scope->employeeId !== null) {
            $assignments = $this->assignmentRepo->findByTicket($ticketId);
            $isAssigned = false;
            foreach ($assignments as $assignment) {
                if ($assignment->employeeId() === $scope->employeeId) {
                    $isAssigned = true;
                    break;
                }
            }

            if (!$isAssigned) {
                throw EmployeeNotAuthorizedException::withId($scope->employeeId, 'registrar repuestos en este ticket');
            }
        }

        $sparePart = TicketSparePart::register(
            id:                TicketSparePartIdValueObject::fromString($this->idGenerator->generate()),
            ticketId:          $ticketId,
            findingId:         TicketFindingIdValueObject::fromString($command->findingId),
            productCode:       $command->productCode,
            serialNumber:      $command->serialNumber,
            quantity:          $command->quantity,
            isIncoming:        $command->isIncoming,
            isVisibleToClient: $command->isVisibleToClient,
        );

        $this->sparePartRepo->save($sparePart);
    }
}
