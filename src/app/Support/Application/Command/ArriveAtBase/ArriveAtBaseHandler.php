<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ArriveAtBase;

use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class ArriveAtBaseHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {
    }

    public function __invoke(ArriveAtBaseCommand $command): void
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($command->ticketId)
        );

        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        $ticket->arriveAtBase();
        $this->ticketRepo->save($ticket);
    }
}
