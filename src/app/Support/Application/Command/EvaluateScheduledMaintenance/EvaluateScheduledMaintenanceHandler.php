<?php

declare(strict_types=1);

namespace App\Support\Application\Command\EvaluateScheduledMaintenance;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\ScheduledMaintenanceRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class EvaluateScheduledMaintenanceHandler
{
    public function __construct(
        private ScheduledMaintenanceRepositoryInterface $scheduledMaintenanceRepo,
        private TicketRepositoryInterface               $ticketRepo,
        private IdGeneratorInterface                    $idGenerator,
    ) {
    }

    public function __invoke(EvaluateScheduledMaintenanceCommand $command): EvaluateScheduledMaintenanceResult
    {
        $now = new \DateTimeImmutable();
        $dueMaintenances = $this->scheduledMaintenanceRepo->findOverdue($now);
        $ticketsCreated = 0;
        $alertsSent = 0;

        foreach ($dueMaintenances as $maintenance) {
            $year = (int) $now->format('Y');
            $sequence = $this->ticketRepo->nextTicketSequence($year);
            $ticketNumber = sprintf('TK-%d-%06d', $year, $sequence);

            $ticketId = TicketIdValueObject::fromString($this->idGenerator->generate());

            $ticket = Ticket::create(
                id:                   $ticketId,
                ticketNumber:         $ticketNumber,
                clientId:             $maintenance->clientId(),
                terminalAssignmentId: $maintenance->terminalAssignmentId(),
                serviceType:          ServiceType::MANTENIMIENTO,
                description:          sprintf(
                    'Mantenimiento preventivo programado (%s) - %s',
                    $maintenance->frequency()->label(),
                    $maintenance->modelId(),
                ),
                createdBy:            $maintenance->createdBy(),
            );

            $this->ticketRepo->save($ticket);
            $maintenance->scheduleNext();
            $this->scheduledMaintenanceRepo->save($maintenance);
            $ticketsCreated++;
        }

        $dueSoon = $this->scheduledMaintenanceRepo->findDueSoon($now);
        $alertsSent = count($dueSoon);

        return new EvaluateScheduledMaintenanceResult(
            ticketsCreated:   $ticketsCreated,
            alertsSent:       $alertsSent,
            dueMaintenances:  count($dueMaintenances),
            upcomingAlerts:   $alertsSent - $ticketsCreated,
        );
    }
}
