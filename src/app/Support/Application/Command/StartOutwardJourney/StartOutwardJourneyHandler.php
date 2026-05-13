<?php

declare(strict_types=1);

namespace App\Support\Application\Command\StartOutwardJourney;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\Journey;
use App\Support\Domain\Repositories\JourneyRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class StartOutwardJourneyHandler
{
    public function __construct(
        private TicketRepositoryInterface  $ticketRepo,
        private JourneyRepositoryInterface $journeyRepo,
        private IdGeneratorInterface       $idGenerator,
    ) {}

    public function __invoke(StartOutwardJourneyCommand $command): void
    {
        $ticketId = TicketIdValueObject::fromString($command->ticketId);

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        $journey = Journey::start(
            id:            JourneyIdValueObject::fromString($this->idGenerator->generate()),
            ticketId:      $ticketId,
            type:          $command->journeyType,
            isPartOfRoute: $command->isPartOfRoute,
            notes:         $command->notes,
        );

        $ticket->startOutwardJourney($journey);

        $this->ticketRepo->save($ticket);
        $this->journeyRepo->save($journey);
    }
}
