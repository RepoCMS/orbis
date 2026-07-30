<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\Journey;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Entities\TicketFinding;
use App\Support\Domain\Entities\TicketPause;
use App\Support\Domain\Enums\JourneyType;
use App\Support\Domain\Enums\RescheduleRequestedBy;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPauseIdValueObject;
use App\Support\Domain\ValueObjects\TicketRescheduleIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketTest extends TestCase
{
    private const TICKET_ID       = '01HZ0000000000000000000001';
    private const CLIENT_ID       = '01HZ0000000000000000000002';
    private const TERMINAL_ID     = '01HZ0000000000000000000003';
    private const CREATED_BY      = '01HZ0000000000000000000004';
    private const EMPLOYEE_ID_1   = '01HZ0000000000000000000005';
    private const EMPLOYEE_ID_2   = '01HZ0000000000000000000006';
    private const ASSIGNMENT_ID_1 = '01HZ0000000000000000000007';
    private const ASSIGNMENT_ID_2 = '01HZ0000000000000000000008';
    private const JOURNEY_ID_1    = '01HZ0000000000000000000009';
    private const PAUSE_ID_1      = '01HZ0000000000000000000010';
    private const FINDING_ID_1    = '01HZ0000000000000000000011';
    private const RESCHEDULE_ID_1 = '01HZ0000000000000000000012';

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createTestTicket(): Ticket
    {
        $ticket = Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000042',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio en sucursal norte',
            createdBy: self::CREATED_BY,
        );
        // Clear the TicketCreated event so subsequent state changes have clean slate
        $ticket->pullDomainEvents();
        return $ticket;
    }

    private function assignAndJourneyToAssistance(Ticket $ticket): void
    {
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );
        $ticket->startOutwardJourney($journey);
        $ticket->arriveAtSite();
    }

    // ─── Test: CreateTicket ───────────────────────────────────────────────────

    public function testCreateTicketFactoryCreatesCorrectly(): void
    {
        // Create ticket directly (not via helper) so we can assert TicketCreated event
        $ticket = Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000042',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio en sucursal norte',
            createdBy: self::CREATED_BY,
        );

        self::assertSame(self::TICKET_ID, (string) $ticket->id());
        self::assertSame('TK-2026-000042', $ticket->ticketNumber());
        self::assertSame(self::CLIENT_ID, $ticket->clientId());
        self::assertSame(self::TERMINAL_ID, $ticket->terminalAssignmentId());
        self::assertSame(ServiceType::CORRECTIVO, $ticket->serviceType());
        self::assertSame('ATM fuera de servicio en sucursal norte', $ticket->description());
        self::assertSame(self::CREATED_BY, $ticket->createdBy());
        self::assertSame(TicketStatus::CREATED, $ticket->status());
        self::assertNull($ticket->scheduledAt());
        self::assertNull($ticket->slaDeadlineAt());
        self::assertNull($ticket->closedAt());
        self::assertNull($ticket->cancelledAt());
        self::assertNull($ticket->cancellationReason());
        self::assertFalse($ticket->isDeleted());

        // Domain events should be recorded
        $events = $ticket->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(\App\Support\Domain\Events\TicketCreated::class, $events[0]);
    }

    public function testCreateThrowsOnEmptyClientId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del cliente es obligatorio');

        Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000042',
            clientId: '',
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        );
    }

    public function testCreateThrowsOnEmptyTerminalAssignmentId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del terminal es obligatorio');

        Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000042',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: '',
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        );
    }

    public function testCreateThrowsOnEmptyDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La descripción del problema es obligatoria');

        Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000042',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: '',
            createdBy: self::CREATED_BY,
        );
    }

    // ─── Test: assign() ───────────────────────────────────────────────────────

    public function testAssignCreatesAssignments(): void
    {
        $ticket = $this->createTestTicket();

        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);
        $assignment1 = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $assignment2 = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_2),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_2,
            isInCharge: false,
        );

        $ticket->assign([$assignment1, $assignment2], new \DateTimeImmutable('+2 hours'), 4);

        self::assertSame(TicketStatus::ASSIGNED, $ticket->status());
        self::assertCount(2, $ticket->assignments());
        self::assertNotNull($ticket->scheduledAt());
        self::assertNotNull($ticket->slaDeadlineAt());

        $events = $ticket->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(\App\Support\Domain\Events\TicketAssigned::class, $events[0]);
    }

    public function testAssignThrowsIfNoInCharge(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: false,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exactamente un técnico encargado');
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));
    }

    // ─── Test: startOutwardJourney() ──────────────────────────────────────────

    public function testStartOutwardJourneyTransitionsToInTransitOut(): void
    {
        $ticket = $this->createTestTicket();

        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);
        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );
        $ticket->startOutwardJourney($journey);

        self::assertSame(TicketStatus::IN_TRANSIT_OUT, $ticket->status());
        self::assertNotNull($ticket->inTransitOutAt());
        self::assertCount(1, $ticket->journeys());
    }

    public function testCannotStartOutwardJourneyIfNotAssigned(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('created');
        $ticket->startOutwardJourney($journey);
    }

    // ─── Test: arriveAtSite() ─────────────────────────────────────────────────

    public function testArriveAtSiteTransitionsToInAssistance(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        self::assertSame(TicketStatus::IN_ASSISTANCE, $ticket->status());
        self::assertNotNull($ticket->assistanceStartAt());
    }

    // ─── Test: pause() / resume() ─────────────────────────────────────────────

    public function testPauseCreatesPauseRecordAndTransitions(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $pause = TicketPause::start(
            id: TicketPauseIdValueObject::fromString(self::PAUSE_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            pauseReason: 'Repuesto no disponible',
        );
        $ticket->pause($pause);

        self::assertSame(TicketStatus::PAUSED, $ticket->status());
        self::assertCount(1, $ticket->pauses());
        $pauses = $ticket->pauses();
        self::assertTrue($pauses[0]->isActive());
    }

    public function testResumeTransitionsBackToInAssistance(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $pause = TicketPause::start(
            id: TicketPauseIdValueObject::fromString(self::PAUSE_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            pauseReason: 'Repuesto no disponible',
        );
        $ticket->pause($pause);
        $ticket->resume();

        self::assertSame(TicketStatus::IN_ASSISTANCE, $ticket->status());
        $pauses = $ticket->pauses();
        self::assertNotNull($pauses[0]->resumedAt());
        self::assertFalse($pauses[0]->isActive());
    }

    // ─── Test: completeAssistance() ───────────────────────────────────────────

    public function testCompleteAssistanceTransitionsToInTransitBack(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        // Register finding first
        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display LCD dañado',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding);

        $ticket->completeAssistance();

        self::assertSame(TicketStatus::IN_TRANSIT_BACK, $ticket->status());
        self::assertNotNull($ticket->inTransitBackAt());
    }

    public function testCompleteAssistanceThrowsIfNoFinding(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('diagnóstico');
        $ticket->completeAssistance();
    }

    // ─── Test: arriveAtBase() ─────────────────────────────────────────────────

    public function testArriveAtBaseTransitionsToPendingClosure(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display LCD dañado',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding);
        $ticket->completeAssistance();
        $ticket->arriveAtBase();

        self::assertSame(TicketStatus::PENDING_CLOSURE, $ticket->status());
    }

    // ─── Test: approve() ──────────────────────────────────────────────────────

    public function testApproveTransitionsToClosed(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);
        // Clear events from assign + journey
        $ticket->pullDomainEvents();

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display LCD dañado',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding);
        $ticket->completeAssistance();
        $ticket->arriveAtBase();

        // Clear events from intermediate transitions
        $ticket->pullDomainEvents();

        $ticket->approve(self::CREATED_BY, 'CLI-GYE-ATM01-20260519-0001');

        self::assertSame(TicketStatus::CLOSED, $ticket->status());
        self::assertNotNull($ticket->closedAt());
        self::assertNotNull($ticket->finding());
        self::assertNotNull($ticket->finding()->catCode());
        self::assertSame('CLI-GYE-ATM01-20260519-0001', $ticket->finding()->catCode());

        $events = $ticket->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(\App\Support\Domain\Events\TicketClosed::class, $events[0]);
    }

    // ─── Test: cancel() ───────────────────────────────────────────────────────

    public function testCancelTransitionsToCancelled(): void
    {
        $ticket = $this->createTestTicket();
        $ticket->cancel('Cliente solicitó cancelación', self::CREATED_BY);

        self::assertSame(TicketStatus::CANCELLED, $ticket->status());
        self::assertNotNull($ticket->cancelledAt());
        self::assertSame('Cliente solicitó cancelación', $ticket->cancellationReason());

        $events = $ticket->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(\App\Support\Domain\Events\TicketCancelled::class, $events[0]);
    }

    public function testCannotCancelIfAlreadyClosed(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display LCD dañado',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding);
        $ticket->completeAssistance();
        $ticket->arriveAtBase();
        $ticket->approve(self::CREATED_BY, 'CLI-GYE-ATM01-0001');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('terminal');
        $ticket->cancel('Intento de cancelación tardía', self::CREATED_BY);
    }

    public function testCannotCancelIfAlreadyCancelled(): void
    {
        $ticket = $this->createTestTicket();
        $ticket->cancel('Primera cancelación', self::CREATED_BY);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('terminal');
        $ticket->cancel('Segunda cancelación', self::CREATED_BY);
    }

    public function testCannotStartJourneyIfNotAssigned(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('created');
        $ticket->startOutwardJourney($journey);
    }

    // ─── Test: reschedule() ───────────────────────────────────────────────────

    public function testRescheduleChangesDate(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $newDate = new \DateTimeImmutable('+5 days');
        $reschedule = \App\Support\Domain\Entities\TicketReschedule::create(
            id: TicketRescheduleIdValueObject::fromString(self::RESCHEDULE_ID_1),
            ticketId: $ticketId,
            previousScheduledAt: new \DateTimeImmutable(),
            newScheduledAt: $newDate,
            reason: 'Cliente solicitó cambio de fecha',
            requestedBy: RescheduleRequestedBy::CLIENT,
            rescheduledBy: self::CREATED_BY,
        );

        // Must set a scheduled at first (assign sets it)
        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));
        // Clear the TicketAssigned event
        $ticket->pullDomainEvents();

        $ticket->reschedule(
            newDate: $newDate,
            reason: 'Cliente solicitó cambio de fecha',
            requestedBy: RescheduleRequestedBy::CLIENT,
            rescheduledBy: self::CREATED_BY,
            rescheduleRecord: $reschedule,
            slaMaxResponseHours: 4,
        );

        self::assertSame($newDate, $ticket->scheduledAt());
        self::assertCount(1, $ticket->reschedules());

        $events = $ticket->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(\App\Support\Domain\Events\TicketRescheduled::class, $events[0]);
    }

    // ─── Test: registerFinding() ──────────────────────────────────────────────

    public function testRegisterFindingSetsFinding(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display LCD dañado',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding);

        self::assertNotNull($ticket->finding());
        self::assertSame('FALLA_DISPLAY', $ticket->finding()->problemTypeFound());
    }

    // ─── Test: softDelete() ───────────────────────────────────────────────────

    public function testSoftDeleteMarksAsDeleted(): void
    {
        $ticket = $this->createTestTicket();
        $ticket->softDelete();

        self::assertTrue($ticket->isDeleted());
        self::assertNotNull($ticket->deletedAt());
    }

    // ─── Test: updateDescription() ────────────────────────────────────────────

    public function testUpdateDescriptionChangesDescription(): void
    {
        $ticket = $this->createTestTicket();
        $ticket->updateDescription('Nueva descripción actualizada');

        self::assertSame('Nueva descripción actualizada', $ticket->description());
    }

    // ─── Test: updateServiceType() ────────────────────────────────────────────

    public function testUpdateServiceTypeChangesType(): void
    {
        $ticket = $this->createTestTicket();
        $ticket->updateServiceType(ServiceType::INSTALACION);

        self::assertSame(ServiceType::INSTALACION, $ticket->serviceType());
    }

    // ─── Test: addEvidence() ──────────────────────────────────────────────────

    public function testAddEvidenceAppendsToList(): void
    {
        $ticket = $this->createTestTicket();

        $evidence = \App\Support\Domain\Entities\TicketEvidence::upload(
            id: \App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject::fromString('01HZ0000000000000000000020'),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            filePath: '/uploads/photo.jpg',
            uploadedBy: self::EMPLOYEE_ID_1,
        );
        $ticket->addEvidence($evidence);

        self::assertCount(1, $ticket->evidences());
    }

    public function testCannotAddEvidenceOnTerminalStatus(): void
    {
        $ticket = $this->createTestTicket();
        $ticket->cancel('Cancelado', self::CREATED_BY);

        $evidence = \App\Support\Domain\Entities\TicketEvidence::upload(
            id: \App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject::fromString('01HZ0000000000000000000023'),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            filePath: '/uploads/test.jpg',
            uploadedBy: self::EMPLOYEE_ID_1,
        );

        $this->expectException(\LogicException::class);
        $ticket->addEvidence($evidence);
    }

    // ─── Test: Terminal states ────────────────────────────────────────────────

    public function testStatusIsNotTerminalAfterCreate(): void
    {
        $ticket = $this->createTestTicket();
        self::assertFalse($ticket->status()->isTerminal());
    }

    public function testStatusIsTerminalAfterCancel(): void
    {
        $ticket = $this->createTestTicket();
        $ticket->cancel('Prueba', self::CREATED_BY, 'CLI-GYE-ATM01-20260519-0001');
        self::assertTrue($ticket->status()->isTerminal());
    }

    public function testStatusIsTerminalAfterClose(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding);
        $ticket->completeAssistance();
        $ticket->arriveAtBase();
        $ticket->approve(self::CREATED_BY, 'CAT-CODE');

        self::assertTrue($ticket->status()->isTerminal());
    }

    // ─── Test: canBeCancelled / canBeRescheduled ──────────────────────────────

    public function testCreatedStatusCanBeCancelledAndRescheduled(): void
    {
        self::assertTrue(TicketStatus::CREATED->canBeCancelled());
        self::assertTrue(TicketStatus::CREATED->canBeRescheduled());
    }

    public function testClosedStatusCannotBeCancelled(): void
    {
        self::assertFalse(TicketStatus::CLOSED->canBeCancelled());
    }

    public function testCancelledStatusCannotBeCancelled(): void
    {
        self::assertFalse(TicketStatus::CANCELLED->canBeCancelled());
    }

    // ─── Test: reassign() ─────────────────────────────────────────────────────

    public function testReassignAddsNewAssignment(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $newAssignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_2),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_2,
            isInCharge: false,
        );
        $ticket->reassign($newAssignment, self::EMPLOYEE_ID_1);

        self::assertCount(2, $ticket->assignments());
    }

    // ─── Test: startWaiting / startAssistanceFromWaiting ──────────────────────

    public function testStartWaitingTransitionsToWaiting(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );
        $ticket->startOutwardJourney($journey);
        $ticket->startWaiting();

        self::assertSame(TicketStatus::WAITING, $ticket->status());
        self::assertNotNull($ticket->waitingStartAt());
    }

    public function testStartAssistanceFromWaitingTransitionsToInAssistance(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );
        $ticket->startOutwardJourney($journey);
        $ticket->startWaiting();
        $ticket->startAssistanceFromWaiting();

        self::assertSame(TicketStatus::IN_ASSISTANCE, $ticket->status());
        self::assertNotNull($ticket->assistanceStartAt());
    }

    // ─── Test: Status transition guards ────────────────────────────────────────

    public function testCannotStartOutwardJourneyFromCreated(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );

        $this->expectException(\LogicException::class);
        $ticket->startOutwardJourney($journey);
    }

    public function testCannotArriveAtSiteFromCreated(): void
    {
        $ticket = $this->createTestTicket();

        $this->expectException(\LogicException::class);
        $ticket->arriveAtSite();
    }

    public function testCannotArriveAtSiteFromAssigned(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $this->expectException(\LogicException::class);
        $ticket->arriveAtSite();
    }

    public function testCannotStartWaitingFromAssigned(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $this->expectException(\LogicException::class);
        $ticket->startWaiting();
    }

    public function testCannotCompleteAssistanceFromInTransitOut(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );
        $ticket->startOutwardJourney($journey);

        $this->expectException(\LogicException::class);
        $ticket->completeAssistance();
    }

    public function testCannotArriveAtBaseFromInAssistance(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );
        $ticket->startOutwardJourney($journey);
        $ticket->arriveAtSite();

        $this->expectException(\LogicException::class);
        $ticket->arriveAtBase();
    }

    // ─── Test: Cancel from any non-terminal status ──────────────────────────

    public function testCancelFromAssigned(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $ticket->cancel('Razón desde asignado', self::CREATED_BY);

        self::assertSame(TicketStatus::CANCELLED, $ticket->status());
    }

    public function testCancelFromInTransitOut(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::JOURNEY_ID_1),
            ticketId: $ticketId,
            type: JourneyType::OUTWARD,
        );
        $ticket->startOutwardJourney($journey);

        $ticket->cancel('Razón desde tránsito', self::CREATED_BY);

        self::assertSame(TicketStatus::CANCELLED, $ticket->status());
    }

    public function testCancelFailsOnClosed(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding);
        $ticket->completeAssistance();
        $ticket->arriveAtBase();
        $ticket->approve(self::CREATED_BY, 'CAT-CODE');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('terminal');
        $ticket->cancel('Intento tardío', self::CREATED_BY);
    }

    // ─── Test: registerFinding with CatCode context ──────────────────────────

    public function testRegisterFindingFromInAssistance(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display LCD dañado',
            batteryVoltageOk: false,
            solution: 'Reemplazo completo de display',
            registeredBy: self::EMPLOYEE_ID_1,
        );

        // Clear events from assign + journey + arrive
        $ticket->pullDomainEvents();

        $ticket->registerFinding($finding);

        self::assertNotNull($ticket->finding());
        self::assertSame('FALLA_DISPLAY', $ticket->finding()->problemTypeFound());
        self::assertFalse($ticket->finding()->batteryVoltageOk());
        self::assertSame('Reemplazo completo de display', $ticket->finding()->solution());
    }

    public function testCannotRegisterFindingTwice(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $finding1 = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: self::EMPLOYEE_ID_1,
        );
        $ticket->registerFinding($finding1);

        $finding2 = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString('01HZ0000000000000000000013'),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA_2',
            problemDetail: 'Otro detalle',
            batteryVoltageOk: true,
            solution: 'Otra solución',
            registeredBy: self::EMPLOYEE_ID_1,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('diagnóstico');
        $ticket->registerFinding($finding2);
    }

    public function testCannotRegisterFindingInCreated(): void
    {
        $ticket = $this->createTestTicket();

        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::FINDING_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: self::EMPLOYEE_ID_1,
        );

        $this->expectException(\LogicException::class);
        $ticket->registerFinding($finding);
    }

    // ─── Test: pause / resume guards ─────────────────────────────────────────

    public function testCannotPauseFromCreated(): void
    {
        $ticket = $this->createTestTicket();

        $pause = TicketPause::start(
            id: TicketPauseIdValueObject::fromString(self::PAUSE_ID_1),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            pauseReason: 'Motivo',
        );

        $this->expectException(\LogicException::class);
        $ticket->pause($pause);
    }

    public function testCannotResumeWithoutPause(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $this->expectException(\LogicException::class);
        $ticket->resume();
    }

    public function testCannotResumeFromInAssistance(): void
    {
        $ticket = $this->createTestTicket();
        $this->assignAndJourneyToAssistance($ticket);

        $this->expectException(\LogicException::class);
        $ticket->resume();
    }

    // ─── Test: startAssistanceFromWaiting guard ──────────────────────────────

    public function testCannotStartAssistanceFromWaitingIfNotWaiting(): void
    {
        $ticket = $this->createTestTicket();
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID_1),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID_1,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+2 hours'));

        $this->expectException(\LogicException::class);
        $ticket->startAssistanceFromWaiting();
    }
}
