<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ArriveAtSite;

use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class ArriveAtSiteHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {}

    public function __invoke(ArriveAtSiteCommand $command): void
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($command->ticketId)
        );

        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        $ticket->arriveAtSite();
        $this->ticketRepo->save($ticket);
    }
}
