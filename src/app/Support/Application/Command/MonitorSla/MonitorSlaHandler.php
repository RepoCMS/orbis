<?php

declare(strict_types=1);

namespace App\Support\Application\Command\MonitorSla;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAuditLog;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Events\SlaAboutToExpire;
use App\Support\Domain\Events\SlaBreached;
use App\Support\Domain\Repositories\TicketAuditLogRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAuditLogIdValueObject;

/**
 * Handler que monitorea tickets con SLA próximo a vencer o vencido.
 *
 * Lógica:
 *  1. Busca tickets en estados no-terminales con slaDeadlineAt no nulo.
 *  2. Para cada ticket verifica el tiempo restante contra el deadline:
 *     a. < 1 hora → alerta "sla_about_to_expire" + DomainEvent
 *     b. < 30 min → alerta "sla_critical"
 *     c. <= 0 (vencido) → alerta "sla_breached" + DomainEvent
 *  3. Usa TicketAuditLog como flag para evitar duplicación de alertas.
 */
final readonly class MonitorSlaHandler
{
    private const SLA_FIELD_ABOUT_TO_EXPIRE = 'sla_about_to_expire';
    private const SLA_FIELD_CRITICAL = 'sla_critical';
    private const SLA_FIELD_BREACHED = 'sla_breached';
    private const CHANGED_BY_SYSTEM = 'system';

    /** Umbral en segundos: 1 hora */
    private const int THRESHOLD_ABOUT_TO_EXPIRE = 3600;

    /** Umbral en segundos: 30 minutos */
    private const int THRESHOLD_CRITICAL = 1800;

    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepository,
        private readonly TicketAuditLogRepositoryInterface $auditLogRepository,
        private readonly DomainEventPublisherInterface $eventPublisher,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(MonitorSlaCommand $command): MonitorSlaResult
    {
        $nonTerminalStatuses = [
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

        $tickets = $this->ticketRepository->findByStatuses($nonTerminalStatuses);
        $now = new \DateTimeImmutable();
        $aboutToExpireAlerts = 0;
        $criticalAlerts = 0;
        $breachedAlerts = 0;

        foreach ($tickets as $ticket) {
            $slaDeadline = $ticket->slaDeadlineAt();

            if ($slaDeadline === null) {
                continue;
            }

            // Cargar audit logs existentes para detectar duplicados
            $existingLogs = $this->auditLogRepository->findByTicketId((string) $ticket->id());
            $existingFieldNames = array_map(
                static fn (TicketAuditLog $log): string => $log->fieldName(),
                $existingLogs,
            );

            $diffSeconds = $slaDeadline->getTimestamp() - $now->getTimestamp();
            $domainEvents = [];

            // a. SLA próximo a vencer: menos de 1 hora
            if (
                $diffSeconds > 0
                && $diffSeconds < self::THRESHOLD_ABOUT_TO_EXPIRE
                && !in_array(self::SLA_FIELD_ABOUT_TO_EXPIRE, $existingFieldNames, true)
            ) {
                $this->createAuditLog($ticket, self::SLA_FIELD_ABOUT_TO_EXPIRE);
                $domainEvents[] = new SlaAboutToExpire(
                    ticketId: (string) $ticket->id(),
                    ticketNumber: $ticket->ticketNumber(),
                    slaDeadlineAt: $slaDeadline,
                    inChargeEmployeeId: $this->resolveInChargeEmployeeId($ticket),
                    assignedEmployeeIds: $this->resolveAllEmployeeIds($ticket),
                );
                $aboutToExpireAlerts++;
            }

            // c. SLA crítico: menos de 30 minutos
            if (
                $diffSeconds > 0
                && $diffSeconds < self::THRESHOLD_CRITICAL
                && !in_array(self::SLA_FIELD_CRITICAL, $existingFieldNames, true)
            ) {
                $this->createAuditLog($ticket, self::SLA_FIELD_CRITICAL);
                $criticalAlerts++;
            }

            // b. SLA vencido: deadline ya pasó
            if (
                $diffSeconds <= 0
                && !in_array(self::SLA_FIELD_BREACHED, $existingFieldNames, true)
            ) {
                $this->createAuditLog($ticket, self::SLA_FIELD_BREACHED);
                $domainEvents[] = new SlaBreached(
                    ticketId: (string) $ticket->id(),
                    ticketNumber: $ticket->ticketNumber(),
                    slaDeadlineAt: $slaDeadline,
                    inChargeEmployeeId: $this->resolveInChargeEmployeeId($ticket),
                    assignedEmployeeIds: $this->resolveAllEmployeeIds($ticket),
                );
                $breachedAlerts++;
            }

            // Publicar Domain Events para notificaciones cross-BC
            if ($domainEvents !== []) {
                $this->eventPublisher->publish(...$domainEvents);
            }
        }

        return new MonitorSlaResult(
            totalTickets: count($tickets),
            aboutToExpireAlerts: $aboutToExpireAlerts,
            criticalAlerts: $criticalAlerts,
            breachedAlerts: $breachedAlerts,
        );
    }

    private function createAuditLog(Ticket $ticket, string $fieldName): void
    {
        $logId = new TicketAuditLogIdValueObject($this->idGenerator->generate());
        $deadlineStr = $ticket->slaDeadlineAt() !== null
            ? $ticket->slaDeadlineAt()->format('c')
            : '';

        $auditLog = TicketAuditLog::record(
            id: $logId,
            ticketId: (string) $ticket->id(),
            fieldName: $fieldName,
            oldValue: null,
            newValue: $deadlineStr,
            changedBy: self::CHANGED_BY_SYSTEM,
        );

        $this->auditLogRepository->save($auditLog);
    }

    private function resolveInChargeEmployeeId(Ticket $ticket): string
    {
        foreach ($ticket->assignments() as $assignment) {
            if ($assignment->isInCharge()) {
                return $assignment->employeeId();
            }
        }

        return '';
    }

    /** @return string[] */
    private function resolveAllEmployeeIds(Ticket $ticket): array
    {
        $ids = [];
        foreach ($ticket->assignments() as $assignment) {
            $ids[] = $assignment->employeeId();
        }

        return $ids;
    }
}
