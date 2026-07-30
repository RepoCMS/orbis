<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Shared\Domain\Event\DomainEvent;
use App\Support\Domain\Enums\RescheduleRequestedBy;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Events\TicketAssigned;
use App\Support\Domain\Events\TicketCancelled;
use App\Support\Domain\Events\TicketClosed;
use App\Support\Domain\Events\TicketCreated;
use App\Support\Domain\Events\TicketModuleFindingRecorded;
use App\Support\Domain\Events\TicketReassigned;
use App\Support\Domain\Events\TicketRescheduled;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

/**
 * Aggregate Root del BC Support.
 *
 * Gestiona el ciclo de vida completo de una intervención técnica
 * sobre un cajero automático de un cliente.
 *
 * Invariantes clave:
 * - Los estados CLOSED y CANCELLED son terminales
 * - Para cerrar se requiere al menos un TicketFinding registrado
 * - ticketNumber es único e inmutable
 * - Solo un técnico puede ser isInCharge por ticket
 */
final class Ticket
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /** @var TicketAssignment[] */
    private array $assignments = [];

    /** @var Journey[] */
    private array $journeys = [];

    /** @var TicketPause[] */
    private array $pauses = [];

    /** @var TicketReschedule[] */
    private array $reschedules = [];

    /** @var TicketEvidence[] */
    private array $evidences = [];

    private ?TicketFinding $finding = null;

    /** @var TicketModuleFinding[] */
    private array $moduleFindings = [];

    private function __construct(
        private TicketIdValueObject  $id,
        private string               $ticketNumber,
        private string               $clientId,
        private string               $terminalAssignmentId,
        private ServiceType          $serviceType,
        private string               $description,
        private TicketStatus         $status,
        private string               $createdBy,
        private ?\DateTimeImmutable  $scheduledAt,
        private ?\DateTimeImmutable  $slaDeadlineAt,
        private ?\DateTimeImmutable  $inTransitOutAt,
        private ?\DateTimeImmutable  $waitingStartAt,
        private ?\DateTimeImmutable  $assistanceStartAt,
        private ?\DateTimeImmutable  $inTransitBackAt,
        private ?\DateTimeImmutable  $closedAt,
        private ?\DateTimeImmutable  $cancelledAt,
        private ?string              $cancellationReason,
        private ?\DateTimeImmutable  $deletedAt,
        private ?\DateTimeImmutable  $reopenedAt = null,
        private ?string              $reopenedReason = null,
        private ?string              $parentTicketId = null,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Factory
    // ─────────────────────────────────────────────────────────────────────────

    public static function create(
        TicketIdValueObject $id,
        string $ticketNumber,
        string $clientId,
        string $terminalAssignmentId,
        ServiceType $serviceType,
        string $description,
        string $createdBy,
        ?string $parentTicketId = null,
    ): self {
        if (trim($clientId) === '') {
            throw new \InvalidArgumentException('El ID del cliente es obligatorio.');
        }
        if (trim($terminalAssignmentId) === '') {
            throw new \InvalidArgumentException('El ID del terminal es obligatorio.');
        }
        if (trim($description) === '') {
            throw new \InvalidArgumentException('La descripción del problema es obligatoria.');
        }
        if (trim($createdBy) === '') {
            throw new \InvalidArgumentException('El usuario creador es obligatorio.');
        }
        if (trim($ticketNumber) === '') {
            throw new \InvalidArgumentException('El número de ticket es obligatorio.');
        }

        $ticket = new self(
            id: $id,
            ticketNumber: $ticketNumber,
            clientId: $clientId,
            terminalAssignmentId: $terminalAssignmentId,
            serviceType: $serviceType,
            description: $description,
            status: TicketStatus::CREATED,
            createdBy: $createdBy,
            scheduledAt: null,
            slaDeadlineAt: null,
            inTransitOutAt: null,
            waitingStartAt: null,
            assistanceStartAt: null,
            inTransitBackAt: null,
            closedAt: null,
            cancelledAt: null,
            cancellationReason: null,
            deletedAt: null,
            reopenedAt: null,
            reopenedReason: null,
            parentTicketId: $parentTicketId,
        );

        $ticket->recordEvent(new TicketCreated(
            ticketId: (string) $id,
            ticketNumber: $ticketNumber,
            clientId: $clientId,
            terminalAssignmentId: $terminalAssignmentId,
            serviceType: $serviceType->value,
            createdBy: $createdBy,
        ));

        return $ticket;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lifecycle behaviors
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param TicketAssignment[] $newAssignments Objetos ya construidos por la capa de aplicación
     */
    public function assign(
        array $newAssignments,
        ?\DateTimeImmutable $scheduledAt,
        int $slaMaxResponseHours = 0,
    ): void {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::CREATED, 'asignar técnicos');

        if (count($newAssignments) === 0) {
            throw new \InvalidArgumentException('Se debe asignar al menos un técnico.');
        }

        $inChargeCount = 0;
        foreach ($newAssignments as $assignment) {
            if (!$assignment instanceof TicketAssignment) {
                throw new \InvalidArgumentException('Se esperan objetos TicketAssignment.');
            }
            if ($assignment->isInCharge()) {
                $inChargeCount++;
            }
        }

        if ($inChargeCount !== 1) {
            throw new \InvalidArgumentException('Debe haber exactamente un técnico encargado (isInCharge = true).');
        }

        $this->assignments = $newAssignments;
        $this->scheduledAt = $scheduledAt;

        if ($scheduledAt !== null && $slaMaxResponseHours > 0) {
            $this->slaDeadlineAt = $scheduledAt->modify("+{$slaMaxResponseHours} hours");
        }

        $this->status = TicketStatus::ASSIGNED;

        $employeeIds = array_map(fn (TicketAssignment $a) => $a->employeeId(), $newAssignments);
        $inCharge    = array_values(array_filter($newAssignments, fn (TicketAssignment $a) => $a->isInCharge()))[0];

        $this->recordEvent(new TicketAssigned(
            ticketId: (string) $this->id,
            employeeIds: $employeeIds,
            inChargeEmployeeId: $inCharge->employeeId(),
            scheduledAt: $scheduledAt,
        ));
    }

    public function reassign(TicketAssignment $newAssignment, string $previousEmployeeId): void
    {
        $this->guardNotTerminal();

        if (trim($previousEmployeeId) === '') {
            throw new \InvalidArgumentException('El técnico anterior es obligatorio para reasignar.');
        }

        $this->assignments[] = $newAssignment;

        $this->recordEvent(new TicketReassigned(
            ticketId: (string) $this->id,
            newEmployeeId: $newAssignment->employeeId(),
            previousEmployeeId: $previousEmployeeId,
        ));
    }

    public function startOutwardJourney(Journey $journey): void
    {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::ASSIGNED, 'iniciar viaje de ida');

        $this->journeys[]   = $journey;
        $this->inTransitOutAt = new \DateTimeImmutable();
        $this->status         = TicketStatus::IN_TRANSIT_OUT;
    }

    public function arriveAtSite(): void
    {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::IN_TRANSIT_OUT, 'llegar al sitio');

        $this->assistanceStartAt = new \DateTimeImmutable();
        $this->status            = TicketStatus::IN_ASSISTANCE;
    }

    public function startWaiting(): void
    {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::IN_TRANSIT_OUT, 'pasar a espera');

        $this->waitingStartAt = new \DateTimeImmutable();
        $this->status         = TicketStatus::WAITING;
    }

    public function startAssistanceFromWaiting(): void
    {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::WAITING, 'iniciar asistencia desde espera');

        $this->assistanceStartAt = new \DateTimeImmutable();
        $this->status            = TicketStatus::IN_ASSISTANCE;
    }

    public function pause(TicketPause $pause): void
    {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::IN_ASSISTANCE, 'pausar asistencia');

        $this->pauses[] = $pause;
        $this->status   = TicketStatus::PAUSED;
    }

    public function resume(): void
    {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::PAUSED, 'reanudar asistencia');

        // Close the active pause record
        $activePause = $this->activeActivePause();
        if ($activePause === null) {
            throw new \LogicException('No hay pausa activa para reanudar.');
        }
        $activePause->resume();

        $this->status = TicketStatus::IN_ASSISTANCE;
    }

    public function completeAssistance(): void
    {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::IN_ASSISTANCE, 'completar asistencia');

        if ($this->finding === null) {
            throw new \LogicException('Se requiere registrar el diagnóstico antes de completar la asistencia.');
        }

        $this->inTransitBackAt = new \DateTimeImmutable();
        $this->status          = TicketStatus::IN_TRANSIT_BACK;
    }

    public function arriveAtBase(): void
    {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::IN_TRANSIT_BACK, 'llegar a base');

        $this->status = TicketStatus::PENDING_CLOSURE;
    }

    public function approve(string $approverUserId, string $catCode): void
    {
        $this->guardNotTerminal();
        $this->guardStatus(TicketStatus::PENDING_CLOSURE, 'aprobar y cerrar ticket');

        if (trim($approverUserId) === '') {
            throw new \InvalidArgumentException('El aprobador es obligatorio.');
        }
        if (trim($catCode) === '') {
            throw new \InvalidArgumentException('El código CAT es obligatorio.');
        }

        $this->closedAt = new \DateTimeImmutable();
        $this->status   = TicketStatus::CLOSED;

        if ($this->finding !== null) {
            $this->finding->assignCatCode($catCode);
        }

        $this->recordEvent(new TicketClosed(
            ticketId: (string) $this->id,
            catCode: $catCode,
            approvedBy: $approverUserId,
        ));
    }

    public function cancel(string $reason, string $cancelledBy): void
    {
        $this->guardNotTerminal();

        if (!$this->status->canBeCancelled()) {
            throw new \LogicException(
                "No se puede cancelar un ticket en estado {$this->status->value}."
            );
        }
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('El motivo de cancelación es obligatorio.');
        }

        $this->cancelledAt        = new \DateTimeImmutable();
        $this->cancellationReason = $reason;
        $this->status             = TicketStatus::CANCELLED;

        $this->recordEvent(new TicketCancelled(
            ticketId: (string) $this->id,
            reason: $reason,
            cancelledBy: $cancelledBy,
        ));
    }

    public function reschedule(
        \DateTimeImmutable $newDate,
        string $reason,
        RescheduleRequestedBy $requestedBy,
        string $rescheduledBy,
        TicketReschedule $rescheduleRecord,
        int $slaMaxResponseHours = 0,
    ): void {
        $this->guardNotTerminal();

        if (!$this->status->canBeRescheduled()) {
            throw new \LogicException(
                "No se puede reprogramar un ticket en estado {$this->status->value}."
            );
        }
        if ($newDate <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('La nueva fecha debe ser en el futuro.');
        }

        $previous             = $this->scheduledAt ?? new \DateTimeImmutable();
        $this->scheduledAt    = $newDate;
        $this->reschedules[]  = $rescheduleRecord;

        if ($slaMaxResponseHours > 0) {
            $this->slaDeadlineAt = $newDate->modify("+{$slaMaxResponseHours} hours");
        }

        $this->recordEvent(new TicketRescheduled(
            ticketId: (string) $this->id,
            previousScheduledAt: $previous,
            newScheduledAt: $newDate,
            reason: $reason,
            requestedBy: $requestedBy->value,
            rescheduledBy: $rescheduledBy,
        ));
    }

    public function registerFinding(TicketFinding $finding): void
    {
        if ($this->finding !== null) {
            throw new \LogicException('Ya existe un diagnóstico registrado para este ticket.');
        }
        if ($this->status !== TicketStatus::IN_ASSISTANCE) {
            throw new \LogicException('Solo se puede registrar el diagnóstico mientras la asistencia está en curso.');
        }
        $this->finding = $finding;
    }

    public function recordModuleFinding(TicketModuleFinding $finding): void
    {
        $this->guardNotTerminal();
        $this->moduleFindings[] = $finding;
        $this->recordEvent(new TicketModuleFindingRecorded(
            findingId: (string) $finding->id(),
            ticketId: (string) $this->id,
            modulePath: $finding->modulePath()->path,
            componentName: $finding->componentName(),
            findingType: $finding->findingType()->value,
            registeredBy: $finding->registeredBy(),
        ));
    }

    public function addEvidence(TicketEvidence $evidence): void
    {
        $this->guardNotTerminal();
        $this->evidences[] = $evidence;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update (coordinador puede editar antes de aprobación)
    // ─────────────────────────────────────────────────────────────────────────

    public function updateDescription(string $description): void
    {
        $this->guardNotTerminal();
        if (trim($description) === '') {
            throw new \InvalidArgumentException('La descripción no puede estar vacía.');
        }
        $this->description = $description;
    }

    public function updateServiceType(ServiceType $serviceType): void
    {
        $this->guardNotTerminal();
        $this->serviceType = $serviceType;
    }

    public function requestReopen(string $reason, string $requestedBy): void
    {
        if (!$this->status->isTerminal()) {
            throw new \LogicException(
                "Solo se puede solicitar reapertura de un ticket en estado terminal. Estado actual: {$this->status->value}."
            );
        }

        $this->reopenedReason = $reason;
        $this->status = TicketStatus::PENDING_CLOSURE;
    }

    public function approveReopen(string $approvedBy): void
    {
        if ($this->status !== TicketStatus::PENDING_CLOSURE) {
            throw new \LogicException(
                "Solo se puede aprobar reapertura de un ticket pendiente de cierre. Estado actual: {$this->status->value}."
            );
        }

        $this->reopenedAt = new \DateTimeImmutable();
        $this->status = TicketStatus::REOPENED;

        $this->recordEvent(new \App\Support\Domain\Events\TicketReopened(
            ticketId: (string) $this->id,
            reason: $this->reopenedReason ?? '',
            approvedBy: $approvedBy,
        ));
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────────────────

    public function id(): TicketIdValueObject            { return $this->id; }
    public function ticketNumber(): string               { return $this->ticketNumber; }
    public function clientId(): string                   { return $this->clientId; }
    public function terminalAssignmentId(): string       { return $this->terminalAssignmentId; }
    public function serviceType(): ServiceType           { return $this->serviceType; }
    public function description(): string                { return $this->description; }
    public function status(): TicketStatus               { return $this->status; }
    public function createdBy(): string                  { return $this->createdBy; }
    public function scheduledAt(): ?\DateTimeImmutable   { return $this->scheduledAt; }
    public function slaDeadlineAt(): ?\DateTimeImmutable { return $this->slaDeadlineAt; }
    public function inTransitOutAt(): ?\DateTimeImmutable  { return $this->inTransitOutAt; }
    public function waitingStartAt(): ?\DateTimeImmutable  { return $this->waitingStartAt; }
    public function assistanceStartAt(): ?\DateTimeImmutable { return $this->assistanceStartAt; }
    public function inTransitBackAt(): ?\DateTimeImmutable { return $this->inTransitBackAt; }
    public function closedAt(): ?\DateTimeImmutable      { return $this->closedAt; }
    public function cancelledAt(): ?\DateTimeImmutable   { return $this->cancelledAt; }
    public function cancellationReason(): ?string        { return $this->cancellationReason; }
    public function deletedAt(): ?\DateTimeImmutable     { return $this->deletedAt; }
    public function reopenedAt(): ?\DateTimeImmutable    { return $this->reopenedAt; }
    public function reopenedReason(): ?string            { return $this->reopenedReason; }
    public function parentTicketId(): ?string             { return $this->parentTicketId; }

    /** @return TicketAssignment[] */
    public function assignments(): array  { return $this->assignments; }

    /** @return Journey[] */
    public function journeys(): array     { return $this->journeys; }

    /** @return TicketPause[] */
    public function pauses(): array       { return $this->pauses; }

    /** @return TicketReschedule[] */
    public function reschedules(): array  { return $this->reschedules; }

    /** @return TicketEvidence[] */
    public function evidences(): array    { return $this->evidences; }

    public function finding(): ?TicketFinding { return $this->finding; }

    /** @return TicketModuleFinding[] */
    public function moduleFindings(): array { return $this->moduleFindings; }

    public function isDeleted(): bool { return $this->deletedAt !== null; }

    // ─────────────────────────────────────────────────────────────────────────
    // Domain events
    // ─────────────────────────────────────────────────────────────────────────

    /** @return DomainEvent[] */
    public function pullDomainEvents(): array
    {
        $events            = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guards
    // ─────────────────────────────────────────────────────────────────────────

    private function guardNotTerminal(): void
    {
        if ($this->status->isTerminal()) {
            throw new \LogicException(
                "El ticket está en estado terminal ({$this->status->value}) y no admite cambios."
            );
        }
    }

    private function guardStatus(TicketStatus $expected, string $action): void
    {
        if ($this->status !== $expected) {
            throw new \LogicException(
                "Para {$action} el ticket debe estar en estado {$expected->value}. Estado actual: {$this->status->value}."
            );
        }
    }

    private function activeActivePause(): ?TicketPause
    {
        foreach (array_reverse($this->pauses) as $pause) {
            if ($pause->isActive()) {
                return $pause;
            }
        }
        return null;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
