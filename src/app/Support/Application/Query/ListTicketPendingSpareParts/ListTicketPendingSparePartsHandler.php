<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTicketPendingSpareParts;

use App\Support\Application\Query\Response\TicketPendingSparePartResponse;
use App\Support\Application\Service\SparePartViewScopeResolver;
use App\Support\Domain\Repositories\TicketPendingSparePartRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class ListTicketPendingSparePartsHandler
{
    public function __construct(
        private TicketPendingSparePartRepositoryInterface $pendingSparePartRepo,
        private TicketRepositoryInterface $ticketRepo,
        private SparePartViewScopeResolver $scopeResolver,
    ) {
    }

    public function __invoke(ListTicketPendingSparePartsQuery $query): array
    {
        if ($query->userId !== null) {
            $scope = $this->scopeResolver->resolve($query->userId);
            if ($scope !== null && $scope->employeeId !== null) {
                $query = new ListTicketPendingSparePartsQuery(
                    ticketId: $query->ticketId,
                    employeeId: $scope->employeeId,
                );
            }
        }

        if ($query->employeeId !== null) {
            $tickets = $this->ticketRepo->findByEmployeeId($query->employeeId);
            $pendingSpareParts = [];
            foreach ($tickets as $ticket) {
                $parts = $this->pendingSparePartRepo->findByTicket($ticket->id());
                foreach ($parts as $part) {
                    $pendingSpareParts[] = TicketPendingSparePartResponse::fromEntity($part);
                }
            }

            return $pendingSpareParts;
        }

        if ($query->ticketId !== null) {
            $parts = $this->pendingSparePartRepo->findByTicket(
                TicketIdValueObject::fromString($query->ticketId),
            );

            return array_map(
                fn ($part) => TicketPendingSparePartResponse::fromEntity($part),
                $parts,
            );
        }

        return [];
    }
}
