<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListTicketModuleFindings;

use App\Support\Application\Query\Response\TicketModuleFindingResponse;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class ListTicketModuleFindingsHandler
{
    public function __construct(
        private TicketModuleFindingRepositoryInterface $findingRepo,
    ) {
    }

    /**
     * @return TicketModuleFindingResponse[]
     */
    public function __invoke(ListTicketModuleFindingsQuery $query): array
    {
        $findings = $this->findingRepo->findByTicket(
            TicketIdValueObject::fromString($query->ticketId)
        );

        return array_map(
            fn ($finding) => TicketModuleFindingResponse::fromEntity($finding),
            $findings,
        );
    }
}
