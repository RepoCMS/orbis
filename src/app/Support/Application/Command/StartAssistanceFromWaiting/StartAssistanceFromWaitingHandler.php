<?php

declare(strict_types=1);

namespace App\Support\Application\Command\StartAssistanceFromWaiting;

use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class StartAssistanceFromWaitingHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {}

    public function __invoke(StartAssistanceFromWaitingCommand $command): void
    {
        $ticket = $this->ticketRepo->findById(
            TicketIdValueObject::fromString($command->ticketId)
        );

        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        $ticket->startAssistanceFromWaiting();
        $this->ticketRepo->save($ticket);
    }
}
