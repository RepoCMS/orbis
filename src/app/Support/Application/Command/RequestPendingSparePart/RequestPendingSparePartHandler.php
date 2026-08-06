<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RequestPendingSparePart;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Application\Service\SparePartViewScopeResolver;
use App\Support\Domain\Entities\TicketPendingSparePart;
use App\Support\Domain\Exceptions\EmployeeNotAuthorizedException;
use App\Support\Domain\Repositories\TicketAssignmentRepositoryInterface;
use App\Support\Domain\Repositories\TicketPendingSparePartRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPendingSparePartIdValueObject;

final readonly class RequestPendingSparePartHandler
{
    public function __construct(
        private TicketRepositoryInterface                  $ticketRepo,
        private TicketPendingSparePartRepositoryInterface  $pendingSparePartRepo,
        private SparePartViewScopeResolver                 $scopeResolver,
        private TicketAssignmentRepositoryInterface        $assignmentRepo,
        private IdGeneratorInterface                       $idGenerator,
    ) {
    }

    public function __invoke(RequestPendingSparePartCommand $command): void
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
                throw EmployeeNotAuthorizedException::withId($scope->employeeId, 'solicitar repuestos pendientes en este ticket');
            }
        }

        $pendingSparePart = TicketPendingSparePart::register(
            id:          TicketPendingSparePartIdValueObject::fromString($this->idGenerator->generate()),
            ticketId:    $ticketId,
            productCode: $command->productCode,
            quantity:    $command->quantity,
            notes:       $command->notes,
        );

        $this->pendingSparePartRepo->save($pendingSparePart);
    }
}
