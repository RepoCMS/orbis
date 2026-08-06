<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RegisterFinding;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\TicketFinding;
use App\Support\Domain\Repositories\TicketFindingRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class RegisterFindingHandler
{
    public function __construct(
        private TicketRepositoryInterface        $ticketRepo,
        private TicketFindingRepositoryInterface $findingRepo,
        private IdGeneratorInterface             $idGenerator,
    ) {
    }

    public function __invoke(RegisterFindingCommand $command): void
    {
        $ticketId = TicketIdValueObject::fromString($command->ticketId);

        $ticket = $this->ticketRepo->findById($ticketId);
        if ($ticket === null) {
            throw new \InvalidArgumentException("Ticket {$command->ticketId} no encontrado.");
        }

        $finding = TicketFinding::register(
            id:               TicketFindingIdValueObject::fromString($this->idGenerator->generate()),
            ticketId:         $ticketId,
            problemTypeFound: $command->problemTypeFound,
            problemDetail:    $command->problemDetail,
            batteryVoltageOk: $command->batteryVoltageOk,
            solution:         $command->solution,
            registeredBy:     $command->registeredBy,
            physicalDocNumber: $command->physicalDocNumber,
        );

        $ticket->registerFinding($finding);

        $this->ticketRepo->save($ticket);
        $this->findingRepo->save($finding);
    }
}
