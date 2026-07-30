<?php

declare(strict_types=1);

namespace App\Organization\Application\Service;

use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepositoryInterface;

/**
 * Servicio de aplicación que verifica si un empleado tiene items pendientes
 * que bloquean su terminación/desactivación.
 *
 * Consulta cross-BC:
 *   - Support: tickets abiertos asignados al empleado
 *   - Organization: periodos especiales activos del empleado
 */
final readonly class TerminationVerificationService implements TerminationVerificationServiceInterface
{
    /** @var TicketStatus[] Statuses considerados "abiertos" (no terminales) */
    private const OPEN_TICKET_STATUSES = [
        TicketStatus::CREATED,
        TicketStatus::ASSIGNED,
        TicketStatus::IN_TRANSIT_OUT,
        TicketStatus::WAITING,
        TicketStatus::IN_ASSISTANCE,
        TicketStatus::PAUSED,
        TicketStatus::IN_TRANSIT_BACK,
        TicketStatus::PENDING_CLOSURE,
        TicketStatus::REOPENED,
    ];

    public function __construct(
        private TicketRepositoryInterface $ticketRepository,
        private EmployeeSpecialPeriodRepositoryInterface $specialPeriodRepository,
    ) {}

    /**
     * Verifica si hay items pendientes que bloqueen la terminación.
     *
     * @return array<string, array<array{type: string, description: string}>> Items pendientes agrupados por BC
     */
    public function findBlockingItems(EmployeeIdValueObject $employeeId): array
    {
        $pendingItems = [];

        // Support: tickets abiertos asignados al empleado
        $openTickets = $this->ticketRepository->findByEmployeeId((string) $employeeId);
        $openTicketItems = [];
        foreach ($openTickets as $ticket) {
            if (!in_array($ticket->status(), self::OPEN_TICKET_STATUSES, true)) {
                continue;
            }
            $openTicketItems[] = [
                'type' => 'ticket',
                'description' => sprintf(
                    'Ticket #%s — %s',
                    $ticket->ticketNumber(),
                    $ticket->status()->label(),
                ),
            ];
        }
        if (!empty($openTicketItems)) {
            $pendingItems['support'] = $openTicketItems;
        }

        // Organization: periodos especiales activos (SCHEDULED o IN_PROGRESS)
        $activePeriods = $this->specialPeriodRepository->findAllByEmployee($employeeId);
        $activePeriodItems = [];
        foreach ($activePeriods as $period) {
            if ($period->status()->isActive()) {
                $activePeriodItems[] = [
                    'type' => 'special_period',
                    'description' => sprintf(
                        '%s (%s — %s)',
                        $period->type()->label(),
                        $period->startedAt()->format('Y-m-d'),
                        $period->endedAt()->format('Y-m-d'),
                    ),
                ];
            }
        }
        if (!empty($activePeriodItems)) {
            $pendingItems['organization'] = $activePeriodItems;
        }

        return $pendingItems;
    }
}
