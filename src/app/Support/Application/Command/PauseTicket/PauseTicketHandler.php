<?php

declare(strict_types=1);

namespace App\Support\Application\Command\PauseTicket;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\TicketPause;
use App\Support\Domain\Repositories\TicketPauseRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPauseIdValueObject;

final readonly class PauseTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface      $ticketRepo,
        private TicketPauseRepositoryInterface $pauseRepo,
        private IdGeneratorInterface           $idGenerator,
    ) {}

    public function __invoke(PauseTicketCommand $command): void
    {
        $ticketId = TicketIdValueObject::fromString($command->ticketId);

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        $pause = TicketPause::start(
            id:          TicketPauseIdValueObject::fromString($this->idGenerator->generate()),
            ticketId:    $ticketId,
            pauseReason: $command->reason,
        );

        $ticket->pause($pause);

        $this->ticketRepo->save($ticket);
        $this->pauseRepo->save($pause);
    }
}
