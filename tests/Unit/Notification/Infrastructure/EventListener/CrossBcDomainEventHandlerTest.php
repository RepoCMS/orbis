<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure\EventListener;

use App\Client\Application\Command\DeactivateContact\DeactivateContactHandler;
use App\Client\Application\Command\ReactivateContact\ReactivateContactHandler;
use App\Client\Application\Command\SoftDeleteContact\SoftDeleteContactHandler;
use App\Client\Domain\Events\ClientDeactivated;
use App\Client\Domain\Events\ClientDeleted;
use App\Client\Domain\Events\ClientReactivated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Expense\Domain\Events\CashAdvancePaymentPending;
use App\Expense\Domain\Events\CashAdvancePendingReview;
use App\Expense\Domain\Events\CashAdvanceSettled;
use App\Expense\Domain\Events\ExpenseApproved;
use App\Expense\Domain\Events\ExpenseItemRejected;
use App\Identity\Domain\Events\PasswordResetCompleted;
use App\Identity\Domain\Events\UserAccountActivated;
use App\Identity\Domain\Events\UserRegistered;
use App\Inventory\Domain\Events\EmergencyPartUsed;
use App\Inventory\Domain\Events\PartRepaired;
use App\Inventory\Domain\Events\RequestAwaitingTechnicianAcceptance;
use App\Inventory\Domain\Events\RequestCancelled;
use App\Inventory\Domain\Events\RequestFulfilled;
use App\Inventory\Domain\Events\RequestRejected;
use App\Inventory\Domain\Events\RequestSubmitted;
use App\Inventory\Domain\Events\StockBelowThreshold;
use App\Notification\Application\Command\SendNotification\SendNotificationCommand;
use App\Notification\Application\Service\TemplateVariableResolver;
use App\Notification\Domain\Entities\NotificationTemplate;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Repositories\NotificationPreferenceRepositoryInterface;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;
use App\Notification\Domain\Services\ContactResolverInterface;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;
use App\Notification\Infrastructure\Messenger\Handler\CrossBcDomainEventHandler;
use App\Notification\Infrastructure\Service\NotificationDispatcher;
use App\Organization\Domain\Events\SpecialPeriodAutoProcessed;
use App\Organization\Domain\Events\SpecialPeriodScheduled;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use App\Support\Domain\Events\SlaAboutToExpire;
use App\Support\Domain\Events\SlaBreached;
use App\Support\Domain\Events\TicketAssigned;
use App\Support\Domain\Events\TicketCancelled;
use App\Support\Domain\Events\TicketClosed;
use App\Support\Domain\Events\TicketCreated;
use App\Support\Domain\Events\TicketReassigned;
use App\Support\Domain\Events\TicketRescheduled;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Tests for CrossBcDomainEventHandler.
 *
 * Verifica que cada evento de dominio del sistema dispare la notificaci�n
 * correspondiente a trav�s de NotificationDispatcher.
 *
 * Fase 5: Event Subscriber Tests
 *   - T5.1 TicketEventSubscriber (6 events)
 *   - T5.2 ExpenseEventSubscriber (5 events)
 *   - T5.3 IdentityEventSubscriber (3 events)
 *   - T5.4 InventoryEventSubscriber (8 events)
 *   - T5.5 OrganizationEventSubscriber (1 event)
 */
final class CrossBcDomainEventHandlerTest extends TestCase
{
    private const TEMPLATE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FZZ';
    private const LOG_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    /**
     * Crea un NotificationDispatcher listo para testing.
     * Configura mocks para que cada dispatch cree una notificaci�n y la env�e.
     */
    private function createDispatcher(
        MessageBusInterface $messageBus,
    ): NotificationDispatcher {
        $templateRepo = $this->createMock(NotificationTemplateRepositoryInterface::class);
        $templateRepo->method('findByEventTypeAndChannel')
            ->willReturnCallback(function (EventType $eventType, NotificationChannel $channel): ?NotificationTemplate {
                return NotificationTemplate::create(
                    id: new NotificationTemplateIdValueObject(self::TEMPLATE_ID),
                    eventType: $eventType,
                    channel: $channel,
                    subject: 'Test subject',
                    bodyTemplate: 'Test body for {{employeeName}}',
                    isActive: true,
                );
            });

        $preferenceRepo = $this->createMock(NotificationPreferenceRepositoryInterface::class);
        $preferenceRepo->method('findByRecipient')
            ->willReturn([]); // No preferences = enabled by default

        $contactResolver = $this->createMock(ContactResolverInterface::class);
        $contactResolver->method('resolve')
            ->willReturn(['email' => 'test@example.com', 'phone' => '+593999999999', 'name' => 'Test User']);

        $templateResolver = new TemplateVariableResolver($contactResolver);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')
            ->willReturn(self::LOG_ID);

        return new NotificationDispatcher(
            templateRepository: $templateRepo,
            preferenceRepository: $preferenceRepo,
            contactResolver: $contactResolver,
            templateResolver: $templateResolver,
            messageBus: $messageBus,
        );
    }

    /**
     * Crea los handlers reales de contacto con repositorios mockeados.
     * Como todos los handlers son final, no se pueden mockear/stub.
     * En su lugar, se crean instancias reales con dependencias stub que
     * devuelven listas vac�as � as� los tests de eventos no-client son
     * seguros (los handlers nunca se invocan para esos eventos).
     */
    private function createDeactivateHandler(): DeactivateContactHandler
    {
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);

        return new DeactivateContactHandler(
            $contactRepo,
            $this->createStub(DomainEventPublisherInterface::class),
        );
    }

    private function createReactivateHandler(): ReactivateContactHandler
    {
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);

        return new ReactivateContactHandler(
            $contactRepo,
            $this->createStub(DomainEventPublisherInterface::class),
        );
    }

    private function createSoftDeleteHandler(): SoftDeleteContactHandler
    {
        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $transactionManager = $this->createStub(TransactionManagerInterface::class);

        return new SoftDeleteContactHandler($contactRepo, $transactionManager);
    }

    private function createHandler(
        NotificationDispatcher $dispatcher,
        ?ContactRepositoryInterface $contactRepo = null,
    ): CrossBcDomainEventHandler {
        $logger = $this->createStub(LoggerInterface::class);
        $contactRepo ??= $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findByClientId')->willReturn([]);

        return new CrossBcDomainEventHandler(
            dispatcher: $dispatcher,
            contactRepository: $contactRepo,
            deactivateContact: $this->createDeactivateHandler(),
            reactivateContact: $this->createReactivateHandler(),
            softDeleteContact: $this->createSoftDeleteHandler(),
            logger: $logger,
        );
    }

    private function makeDomainEventMessage(object $event): DomainEventMessage
    {
        return DomainEventMessage::fromEvent($event);
    }

    private function createMessageBusExpectingDispatches(int $expectedCount): MessageBusInterface
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->exactly($expectedCount))
            ->method('dispatch')
            ->with($this->isInstanceOf(SendNotificationCommand::class))
            ->willReturn(new Envelope(new \stdClass()));

        return $messageBus;
    }

    // --- T5.1: Ticket Event Subscriber (6 events) -----------------

    public function testHandlesTicketCreated(): void
    {
        $event = new TicketCreated(
            ticketId: '01ARZ3NDEKTSV4RRFFQ69G5FA1',
            ticketNumber: 'TKT-001',
            clientId: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
            terminalAssignmentId: '01ARZ3NDEKTSV4RRFFQ69G5FJ1',
            serviceType: 'mantenimiento',
            createdBy: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3); // IN_APP, EMAIL, WHATSAPP
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesTicketAssigned(): void
    {
        $event = new TicketAssigned(
            ticketId: '01ARZ3NDEKTSV4RRFFQ69G5FA1',
            employeeIds: ['01ARZ3NDEKTSV4RRFFQ69G5FC1'],
            inChargeEmployeeId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
            scheduledAt: new \DateTimeImmutable('2024-06-15 10:00:00'),
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesTicketReassigned(): void
    {
        $event = new TicketReassigned(
            ticketId: '01ARZ3NDEKTSV4RRFFQ69G5FA1',
            previousEmployeeId: '01ARZ3NDEKTSV4RRFFQ69G5FK1',
            newEmployeeId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesTicketRescheduled(): void
    {
        $event = new TicketRescheduled(
            ticketId: '01ARZ3NDEKTSV4RRFFQ69G5FA1',
            previousScheduledAt: new \DateTimeImmutable('2024-06-14 10:00:00'),
            newScheduledAt: new \DateTimeImmutable('2024-06-15 14:00:00'),
            reason: 'Cliente solicit� cambio de horario',
            requestedBy: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
            rescheduledBy: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesTicketCancelled(): void
    {
        $event = new TicketCancelled(
            ticketId: '01ARZ3NDEKTSV4RRFFQ69G5FA1',
            cancelledBy: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
            reason: 'Cliente cancel� el servicio',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesTicketClosed(): void
    {
        $event = new TicketClosed(
            ticketId: '01ARZ3NDEKTSV4RRFFQ69G5FA1',
            catCode: 'CAT-123',
            approvedBy: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    // --- T5.2: Expense Event Subscriber (5 events) ----------------

    public function testHandlesExpenseItemRejected(): void
    {
        $event = new ExpenseItemRejected(
            expenseId: '01ARZ3NDEKTSV4RRFFQ69G5FG1',
            itemId: '01ARZ3NDEKTSV4RRFFQ69G5FL1',
            reviewedBy: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
            rejectionReason: 'Falta evidencia fotogr�fica',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesExpenseApproved(): void
    {
        $event = new ExpenseApproved(
            expenseId: '01ARZ3NDEKTSV4RRFFQ69G5FG1',
            approvedBy: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
            totalAmount: 150.75,
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesCashAdvancePaymentPending(): void
    {
        $event = new CashAdvancePaymentPending(
            cashAdvanceId: '01ARZ3NDEKTSV4RRFFQ69G5FM1',
            employeeId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
            amount: 200.00,
            daysSinceGiven: 5,
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesCashAdvancePendingReview(): void
    {
        $event = new CashAdvancePendingReview(
            cashAdvanceId: '01ARZ3NDEKTSV4RRFFQ69G5FM2',
            employeeId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
            amount: 300.00,
            givenBy: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesCashAdvanceSettled(): void
    {
        $event = new CashAdvanceSettled(
            cashAdvanceId: '01ARZ3NDEKTSV4RRFFQ69G5FM3',
            employeeId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
            amount: 200.00,
            direction: 'employee_to_company',
            confirmedBy: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    // --- T5.3: Identity Event Subscriber (3 events) ---------------

    public function testHandlesUserRegistered(): void
    {
        $event = new UserRegistered(
            userId: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
            username: 'juan.perez',
            roleId: '01ARZ3NDEKTSV4RRFFQ69G5FN1',
        );

        // UserRegistered is NOT a security event (only UserAccountActivated and PasswordResetCompleted are)
        $messageBus = $this->createMessageBusExpectingDispatches(3); // IN_APP, EMAIL, WHATSAPP
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesUserAccountActivated(): void
    {
        $event = new UserAccountActivated(
            userId: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
            username: 'juan.perez',
        );

        // UserAccountActivated IS a security event ? only EMAIL channel
        $messageBus = $this->createMessageBusExpectingDispatches(1); // Only EMAIL
        // Only EMAIL
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesPasswordResetCompleted(): void
    {
        $event = new PasswordResetCompleted(
            userId: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
        );

        // PasswordResetCompleted IS a security event ? only EMAIL channel
        $messageBus = $this->createMessageBusExpectingDispatches(1); // Only EMAIL
        // Only EMAIL
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    // --- T5.4: Inventory Event Subscriber (8 events) -------------

    public function testHandlesStockBelowThreshold(): void
    {
        $event = new StockBelowThreshold(
            productId: '01ARZ3NDEKTSV4RRFFQ69G5FE1',
            locationId: '01ARZ3NDEKTSV4RRFFQ69G5FF1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesEmergencyPartUsed(): void
    {
        $event = new EmergencyPartUsed(
            productId: '01ARZ3NDEKTSV4RRFFQ69G5FE1',
            serializedStockId: '01ARZ3NDEKTSV4RRFFQ69G5FO1',
            employeeId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
            notes: 'Rotura en campo',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesPartRepaired(): void
    {
        $event = new PartRepaired(
            serializedStockId: '01ARZ3NDEKTSV4RRFFQ69G5FO2',
        );

        // PartRepaired has empty recipients, so no dispatches
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('dispatch');
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesRequestSubmitted(): void
    {
        $event = new RequestSubmitted(
            requestId: '01ARZ3NDEKTSV4RRFFQ69G5FP1',
            reference: 'SOL-001',
            requestedByUserId: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
            productId: '01ARZ3NDEKTSV4RRFFQ69G5FE1',
            quantity: 3,
            sourceLocationId: '01ARZ3NDEKTSV4RRFFQ69G5FF1',
            sourceResponsibleUserId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
        );

        // 2 recipients � 3 channels = 6 dispatches
        $messageBus = $this->createMessageBusExpectingDispatches(6);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesRequestAwaitingTechnicianAcceptance(): void
    {
        $event = new RequestAwaitingTechnicianAcceptance(
            requestId: '01ARZ3NDEKTSV4RRFFQ69G5FP2',
            reference: 'SOL-002',
            requestedByUserId: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
            approvedByUserId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesRequestRejected(): void
    {
        $event = new RequestRejected(
            requestId: '01ARZ3NDEKTSV4RRFFQ69G5FP3',
            reference: 'SOL-003',
            requestedByUserId: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
            rejectedByUserId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesRequestCancelled(): void
    {
        $event = new RequestCancelled(
            requestId: '01ARZ3NDEKTSV4RRFFQ69G5FP4',
            reference: 'SOL-004',
            requestedByUserId: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
            sourceLocationId: '01ARZ3NDEKTSV4RRFFQ69G5FF1',
            cancelledByUserId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesRequestFulfilled(): void
    {
        $event = new RequestFulfilled(
            requestId: '01ARZ3NDEKTSV4RRFFQ69G5FP5',
            reference: 'SOL-005',
            requestedByUserId: '01ARZ3NDEKTSV4RRFFQ69G5FD1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    // --- T5.5: Organization Event Subscriber (1 event) ------------

    public function testHandlesSpecialPeriodScheduled(): void
    {
        $event = new SpecialPeriodScheduled(
            periodId: '01ARZ3NDEKTSV4RRFFQ69G5FH1',
            employeeId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
            type: 'vacation',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    // --- SLA events (2) -------------------------------------------

    public function testHandlesSlaAboutToExpire(): void
    {
        $event = new SlaAboutToExpire(
            ticketId: '01ARZ3NDEKTSV4RRFFQ69G5FA1',
            ticketNumber: 'TKT-001',
            slaDeadlineAt: new \DateTimeImmutable('+30 minutes'),
            inChargeEmployeeId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
            assignedEmployeeIds: ['01ARZ3NDEKTSV4RRFFQ69G5FC1'],
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesSlaBreached(): void
    {
        $event = new SlaBreached(
            ticketId: '01ARZ3NDEKTSV4RRFFQ69G5FA1',
            ticketNumber: 'TKT-001',
            slaDeadlineAt: new \DateTimeImmutable('-1 hour'),
            inChargeEmployeeId: '01ARZ3NDEKTSV4RRFFQ69G5FC1',
            assignedEmployeeIds: ['01ARZ3NDEKTSV4RRFFQ69G5FC1'],
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    // --- Client events (3) ----------------------------------------

    public function testHandlesClientDeactivated(): void
    {
        $event = new ClientDeactivated(
            clientId: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
            reason: 'Impago',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesClientReactivated(): void
    {
        $event = new ClientReactivated(
            clientId: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    public function testHandlesClientDeleted(): void
    {
        $event = new ClientDeleted(
            clientId: '01ARZ3NDEKTSV4RRFFQ69G5FB1',
            reason: 'Solicitud del cliente',
        );

        $messageBus = $this->createMessageBusExpectingDispatches(3);
        $dispatcher = $this->createDispatcher($messageBus);
        $handler = $this->createHandler($dispatcher);

        $handler($this->makeDomainEventMessage($event));
    }

    // --- Unhandled event -----------------------------------------

    public function testLogsWarningForUnhandledEvent(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Unhandled domain event'));

        $dispatcher = $this->createDispatcher(
            $this->createStub(MessageBusInterface::class),
        );

        // Use SpecialPeriodAutoProcessed which is NOT handled by CrossBcDomainEventHandler
        $unhandledEvent = new SpecialPeriodAutoProcessed(
            count: 5,
        );

        $handler = new CrossBcDomainEventHandler(
            dispatcher: $dispatcher,
            contactRepository: $this->createMock(ContactRepositoryInterface::class),
            deactivateContact: $this->createDeactivateHandler(),
            reactivateContact: $this->createReactivateHandler(),
            softDeleteContact: $this->createSoftDeleteHandler(),
            logger: $logger,
        );

        $handler($this->makeDomainEventMessage($unhandledEvent));
    }
}
