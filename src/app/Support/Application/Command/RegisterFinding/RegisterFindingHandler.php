<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RegisterFinding;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\TicketFinding;
use App\Support\Domain\Repositories\TicketFindingRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use Doctrine\DBAL\Connection;

final readonly class RegisterFindingHandler
{
    public function __construct(
        private TicketRepositoryInterface        $ticketRepo,
        private TicketFindingRepositoryInterface $findingRepo,
        private IdGeneratorInterface             $idGenerator,
        private Connection                       $connection,
    ) {}

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

        foreach ($command->componentIds as $componentId) {
            if (!\preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $componentId)) {
                continue;
            }
            $this->connection->executeStatement(
                'INSERT INTO ticket_finding_components (id, finding_id, component_id, notes) VALUES (?, ?, ?, NULL)',
                [$this->idGenerator->generate(), (string) $finding->id(), $componentId]
            );
        }
    }
}
