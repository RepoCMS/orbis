<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTicketSpareParts;

use App\Support\Application\Service\SparePartViewScopeResolver;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\Repositories\TicketSparePartRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class ListTicketSparePartsHandler
{
    public function __construct(
        private TicketSparePartRepositoryInterface $sparePartRepo,
        private TicketRepositoryInterface $ticketRepo,
        private SparePartViewScopeResolver $scopeResolver,
    ) {
    }

    public function __invoke(ListTicketSparePartsQuery $query): array
    {
        if ($query->userId !== null) {
            $scope = $this->scopeResolver->resolve($query->userId);
            if ($scope !== null && $scope->employeeId !== null) {
                $query = new ListTicketSparePartsQuery(
                    ticketId: $query->ticketId,
                    employeeId: $scope->employeeId,
                );
            }
        }

        if ($query->employeeId !== null) {
            $tickets = $this->ticketRepo->findByEmployeeId($query->employeeId);
            $spareParts = [];
            foreach ($tickets as $ticket) {
                $parts = $this->sparePartRepo->findByTicket($ticket->id());
                foreach ($parts as $part) {
                    $spareParts[] = $part;
                }
            }

            return $spareParts;
        }

        if ($query->ticketId !== null) {
            return $this->sparePartRepo->findByTicket(
                TicketIdValueObject::fromString($query->ticketId),
            );
        }

        return [];
    }
}
