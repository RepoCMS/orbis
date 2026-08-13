<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Messenger\Handler;

use App\Client\Application\Command\DeactivateContact\DeactivateContactCommand;
use App\Client\Application\Command\DeactivateContact\DeactivateContactHandler;
use App\Client\Application\Command\ReactivateContact\ReactivateContactCommand;
use App\Client\Application\Command\ReactivateContact\ReactivateContactHandler;
use App\Client\Application\Command\SoftDeleteContact\SoftDeleteContactCommand;
use App\Client\Application\Command\SoftDeleteContact\SoftDeleteContactHandler;
use App\Client\Domain\Events\ClientCreated;
use App\Client\Domain\Events\ClientDeactivated;
use App\Client\Domain\Events\ClientDeleted;
use App\Client\Domain\Events\ClientReactivated;
use App\Client\Domain\Events\ClientSaleOrderCompleted;
use App\Client\Domain\Events\ClientSaleOrderCreated;
use App\Client\Domain\Events\ClientUpdated;
use App\Client\Domain\Events\ContactAccessProvisioned;
use App\Client\Domain\Events\ContactAdded;
use App\Client\Domain\Events\ContactDeactivated;
use App\Client\Domain\Events\ContactReactivated;
use App\Client\Domain\Events\ContactUpdated;
use App\Client\Domain\Events\ContactUserLinked;
use App\Client\Domain\Events\ContactUserUnlinked;
use App\Client\Domain\Events\ContractCancelled;
use App\Client\Domain\Events\ContractCreated;
use App\Client\Domain\Events\ContractUpdated;
use App\Client\Domain\Events\SlaPolicyCreated;
use App\Client\Domain\Events\TerminalAssignmentDeactivated;
use App\Client\Domain\Events\TerminalAssignmentReactivated;
use App\Client\Domain\Events\TerminalAssignmentRegistered;
use App\Client\Domain\Events\TerminalAssignmentUpdated;
use App\Client\Domain\Events\TerminalDeactivated;
use App\Client\Domain\Events\TerminalReactivated;
use App\Client\Domain\Events\TerminalRegistered;
use App\Client\Domain\Events\TerminalUpdated;
use App\Client\Domain\Events\WarrantyExpired;
use App\Client\Domain\Events\WarrantyRegistered;
use App\Client\Domain\Events\WarrantyUpdated;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Expense\Domain\Events\CashAdvanceCreated;
use App\Expense\Domain\Events\CashAdvancePaymentPending;
use App\Expense\Domain\Events\CashAdvancePendingReview;
use App\Expense\Domain\Events\CashAdvanceSettled;
use App\Expense\Domain\Events\CashAdvanceUpdated;
use App\Expense\Domain\Events\ExpenseApproved;
use App\Expense\Domain\Events\ExpenseItemRejected;
use App\Expense\Domain\Events\ExpenseResubmitted;
use App\Expense\Domain\Events\ExpenseSubmitted;
use App\Identity\Domain\Events\AdminSeeded;
use App\Identity\Domain\Events\ContactLinked;
use App\Identity\Domain\Events\EmployeeRegistered;
use App\Identity\Domain\Events\MfaDisabledByAdmin;
use App\Identity\Domain\Events\MfaSetupCompleted;
use App\Identity\Domain\Events\PasswordChanged;
use App\Identity\Domain\Events\PasswordResetCompleted;
use App\Identity\Domain\Events\PasswordResetRequested;
use App\Identity\Domain\Events\RoleCreated;
use App\Identity\Domain\Events\RoleDeleted;
use App\Identity\Domain\Events\RolePermissionsUpdated;
use App\Identity\Domain\Events\UserAccountActivated;
use App\Identity\Domain\Events\UserRegistered;
use App\Identity\Domain\Events\UserUnlocked;
use App\Inventory\Domain\Events\EmergencyPartUsed;
use App\Inventory\Domain\Events\EntryConfirmed;
use App\Inventory\Domain\Events\PartRepaired;
use App\Inventory\Domain\Events\ProductCreated;
use App\Inventory\Domain\Events\RequestApproved;
use App\Inventory\Domain\Events\RequestAwaitingTechnicianAcceptance;
use App\Inventory\Domain\Events\RequestCancelled;
use App\Inventory\Domain\Events\RequestFulfilled;
use App\Inventory\Domain\Events\RequestRejected;
use App\Inventory\Domain\Events\RequestSubmitted;
use App\Inventory\Domain\Events\SerializedStockCreated;
use App\Inventory\Domain\Events\StockBelowThreshold;
use App\Inventory\Domain\Events\StockMoved;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Infrastructure\Service\NotificationDispatcher;
use App\Organization\Domain\Events\CityTransferred;
use App\Organization\Domain\Events\EmployeeAccessProvisioned;
use App\Organization\Domain\Events\EmployeeCreated;
use App\Organization\Domain\Events\EmployeeDeactivated;
use App\Organization\Domain\Events\EmployeeDeleted;
use App\Organization\Domain\Events\EmployeeReactivated;
use App\Organization\Domain\Events\EmployeeUserLinked;
use App\Organization\Domain\Events\EmployeeUserUnlinked;
use App\Organization\Domain\Events\OvertimeApproved;
use App\Organization\Domain\Events\OvertimeCorrected;
use App\Organization\Domain\Events\OvertimeRegistered;
use App\Organization\Domain\Events\OvertimeRejected;
use App\Organization\Domain\Events\OvertimeReviewRequested;
use App\Organization\Domain\Events\ServiceZoneCreated;
use App\Organization\Domain\Events\ServiceZoneDeactivated;
use App\Organization\Domain\Events\ServiceZoneReactivated;
use App\Organization\Domain\Events\SpecialPeriodCancelled;
use App\Organization\Domain\Events\SpecialPeriodCompleted;
use App\Organization\Domain\Events\SpecialPeriodEdited;
use App\Organization\Domain\Events\SpecialPeriodScheduled;
use App\Organization\Domain\Events\VacationApproved;
use App\Organization\Domain\Events\VacationCancelled;
use App\Organization\Domain\Events\VacationRejected;
use App\Organization\Domain\Events\VacationRequested;
use App\Shared\Infrastructure\Messenger\Message\DomainEventMessage;
use App\Support\Domain\Events\SlaAboutToExpire;
use App\Support\Domain\Events\SlaBreached;
use App\Support\Domain\Events\TicketArrivedAtSite;
use App\Support\Domain\Events\TicketAssigned;
use App\Support\Domain\Events\TicketAssistanceCompleted;
use App\Support\Domain\Events\TicketAssistanceStarted;
use App\Support\Domain\Events\TicketCancelled;
use App\Support\Domain\Events\TicketClosed;
use App\Support\Domain\Events\TicketCreated;
use App\Support\Domain\Events\TicketFindingRegistered;
use App\Support\Domain\Events\TicketJourneyStarted;
use App\Support\Domain\Events\TicketPaused;
use App\Support\Domain\Events\TicketReassigned;
use App\Support\Domain\Events\TicketReopened;
use App\Support\Domain\Events\TicketReopenRequested;
use App\Support\Domain\Events\TicketRescheduled;
use App\Support\Domain\Events\TicketResumed;
use App\Support\Domain\Events\TicketWaitingStarted;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asíncrono que procesa todos los Domain Events cross-BC
 * y dispara las notificaciones correspondientes.
 *
 * Antes: 5 EventSubscribers sincrónicos (Organization, Inventory, Identity, Expense, Ticket).
 * Ahora: handler único vía Messenger async que recibe DomainEventMessage.
 *
 * Cada método maneja un evento específico y construye los parámetros
 * necesarios para NotificationDispatcher::dispatchForEvent().
 */
#[AsMessageHandler]
final readonly class CrossBcDomainEventHandler
{
    public function __construct(
        private NotificationDispatcher         $dispatcher,
        private ContactRepositoryInterface     $contactRepository,
        private DeactivateContactHandler       $deactivateContact,
        private ReactivateContactHandler       $reactivateContact,
        private SoftDeleteContactHandler       $softDeleteContact,
        private LoggerInterface                $logger,
    ) {
    }

    public function __invoke(DomainEventMessage $message): void
    {
        match ($message->eventClass) {
            // Organization
            SpecialPeriodScheduled::class => $this->onSpecialPeriodScheduled($message),

            // Inventory
            StockBelowThreshold::class => $this->onStockBelowThreshold($message),
            EmergencyPartUsed::class => $this->onEmergencyPartUsed($message),
            PartRepaired::class => $this->onPartRepaired($message),

            // Identity
            UserRegistered::class => $this->onUserRegistered($message),
            UserAccountActivated::class => $this->onUserAccountActivated($message),
            PasswordResetCompleted::class => $this->onPasswordResetCompleted($message),
            UserUnlocked::class => $this->onUserUnlocked($message),
            PasswordChanged::class => $this->onPasswordChanged($message),
            PasswordResetRequested::class => $this->onPasswordResetRequested($message),
            MfaSetupCompleted::class => $this->onMfaSetupCompleted($message),
            MfaDisabledByAdmin::class => $this->onMfaDisabledByAdmin($message),
            RoleCreated::class => $this->onRoleCreated($message),
            RoleDeleted::class => $this->onRoleDeleted($message),
            RolePermissionsUpdated::class => $this->onRolePermissionsUpdated($message),
            EmployeeRegistered::class => $this->onEmployeeRegistered($message),
            ContactLinked::class => $this->onContactLinked($message),
            AdminSeeded::class => $this->onAdminSeeded($message),

            // Expense
            ExpenseSubmitted::class => $this->onExpenseSubmitted($message),
            ExpenseResubmitted::class => $this->onExpenseResubmitted($message),
            ExpenseItemRejected::class => $this->onExpenseItemRejected($message),
            ExpenseApproved::class => $this->onExpenseApproved($message),
            CashAdvanceCreated::class => $this->onCashAdvanceCreated($message),
            CashAdvanceUpdated::class => $this->onCashAdvanceUpdated($message),
            CashAdvancePaymentPending::class => $this->onCashAdvancePaymentPending($message),
            CashAdvancePendingReview::class => $this->onCashAdvancePendingReview($message),
            CashAdvanceSettled::class => $this->onCashAdvanceSettled($message),

            // Stock Requests
            RequestSubmitted::class => $this->onRequestSubmitted($message),
            RequestAwaitingTechnicianAcceptance::class => $this->onRequestAwaitingTechnicianAcceptance($message),
            RequestRejected::class => $this->onRequestRejected($message),
            RequestCancelled::class => $this->onRequestCancelled($message),
            RequestFulfilled::class => $this->onRequestFulfilled($message),

            // Support
            TicketCreated::class => $this->onTicketCreated($message),
            TicketAssigned::class => $this->onTicketAssigned($message),
            TicketReassigned::class => $this->onTicketReassigned($message),
            TicketRescheduled::class => $this->onTicketRescheduled($message),
            TicketCancelled::class => $this->onTicketCancelled($message),
            TicketClosed::class => $this->onTicketClosed($message),
            TicketArrivedAtSite::class => $this->onTicketArrivedAtSite($message),
            TicketAssistanceStarted::class => $this->onTicketAssistanceStarted($message),
            TicketAssistanceCompleted::class => $this->onTicketAssistanceCompleted($message),
            TicketFindingRegistered::class => $this->onTicketFindingRegistered($message),
            TicketJourneyStarted::class => $this->onTicketJourneyStarted($message),
            TicketPaused::class => $this->onTicketPaused($message),
            TicketWaitingStarted::class => $this->onTicketWaitingStarted($message),
            TicketResumed::class => $this->onTicketResumed($message),
            TicketReopenRequested::class => $this->onTicketReopenRequested($message),
            TicketReopened::class => $this->onTicketReopened($message),
            SlaAboutToExpire::class => $this->onSlaAboutToExpire($message),
            SlaBreached::class => $this->onSlaBreached($message),

            // Inventory - Stock and products
            RequestApproved::class => $this->onRequestApproved($message),
            StockMoved::class => $this->onStockMoved($message),
            SerializedStockCreated::class => $this->onSerializedStockCreated($message),
            EntryConfirmed::class => $this->onEntryConfirmed($message),
            ProductCreated::class => $this->onProductCreated($message),

            // Organization - Employee lifecycle
            EmployeeCreated::class => $this->onEmployeeCreated($message),
            EmployeeReactivated::class => $this->onEmployeeReactivated($message),
            EmployeeDeactivated::class => $this->onEmployeeDeactivated($message),
            EmployeeDeleted::class => $this->onEmployeeDeleted($message),
            EmployeeUserLinked::class => $this->onEmployeeUserLinked($message),
            EmployeeUserUnlinked::class => $this->onEmployeeUserUnlinked($message),
            EmployeeAccessProvisioned::class => $this->onEmployeeAccessProvisioned($message),
            VacationRequested::class => $this->onVacationRequested($message),
            VacationApproved::class => $this->onVacationApproved($message),
            VacationCancelled::class => $this->onVacationCancelled($message),
            VacationRejected::class => $this->onVacationRejected($message),

            // Organization - Overtime
            OvertimeRegistered::class => $this->onOvertimeRegistered($message),
            OvertimeApproved::class => $this->onOvertimeApproved($message),
            OvertimeCorrected::class => $this->onOvertimeCorrected($message),
            OvertimeRejected::class => $this->onOvertimeRejected($message),
            OvertimeReviewRequested::class => $this->onOvertimeReviewRequested($message),

            // Organization - Service zones
            ServiceZoneCreated::class => $this->onServiceZoneCreated($message),
            ServiceZoneDeactivated::class => $this->onServiceZoneDeactivated($message),
            ServiceZoneReactivated::class => $this->onServiceZoneReactivated($message),

            // Organization - Special periods
            SpecialPeriodCancelled::class => $this->onSpecialPeriodCancelled($message),
            SpecialPeriodCompleted::class => $this->onSpecialPeriodCompleted($message),
            SpecialPeriodEdited::class => $this->onSpecialPeriodEdited($message),

            // Organization - Cities
            CityTransferred::class => $this->onCityTransferred($message),

            // Client
            ClientCreated::class => $this->onClientCreated($message),
            ClientUpdated::class => $this->onClientUpdated($message),
            ClientSaleOrderCreated::class => $this->onClientSaleOrderCreated($message),
            ClientSaleOrderCompleted::class => $this->onClientSaleOrderCompleted($message),
            ContactAdded::class => $this->onContactAdded($message),
            ContactUpdated::class => $this->onContactUpdated($message),
            ContactDeactivated::class => $this->onContactDeactivated($message),
            ContactReactivated::class => $this->onContactReactivated($message),
            ContactUserLinked::class => $this->onContactUserLinked($message),
            ContactUserUnlinked::class => $this->onContactUserUnlinked($message),
            ContactAccessProvisioned::class => $this->onContactAccessProvisioned($message),
            TerminalRegistered::class => $this->onTerminalRegistered($message),
            TerminalUpdated::class => $this->onTerminalUpdated($message),
            TerminalDeactivated::class => $this->onTerminalDeactivated($message),
            TerminalReactivated::class => $this->onTerminalReactivated($message),
            TerminalAssignmentRegistered::class => $this->onTerminalAssignmentRegistered($message),
            TerminalAssignmentUpdated::class => $this->onTerminalAssignmentUpdated($message),
            TerminalAssignmentDeactivated::class => $this->onTerminalAssignmentDeactivated($message),
            TerminalAssignmentReactivated::class => $this->onTerminalAssignmentReactivated($message),
            ContractCreated::class => $this->onContractCreated($message),
            ContractUpdated::class => $this->onContractUpdated($message),
            ContractCancelled::class => $this->onContractCancelled($message),
            WarrantyRegistered::class => $this->onWarrantyRegistered($message),
            WarrantyUpdated::class => $this->onWarrantyUpdated($message),
            WarrantyExpired::class => $this->onWarrantyExpired($message),
            SlaPolicyCreated::class => $this->onSlaPolicyCreated($message),
            ClientDeactivated::class => $this->onClientDeactivated($message),
            ClientReactivated::class => $this->onClientReactivated($message),
            ClientDeleted::class => $this->onClientDeleted($message),

            default => $this->logger->warning('Unhandled domain event in CrossBcDomainEventHandler: {eventClass}', [
                'eventClass' => $message->eventClass,
            ]),
        };
    }

    private function onSpecialPeriodScheduled(DomainEventMessage $message): void
    {
        /** @var SpecialPeriodScheduled $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMPLOYEE_SPECIAL_PERIOD_STARTING,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Periodo especial programado',
            bodyTemplate: 'Se ha programado un período especial de tipo {{type}} para el empleado.',
            templateVars: [
                'periodId' => $event->periodId,
                'employeeId' => $event->employeeId,
                'type' => $event->type,
            ],
            referenceType: 'special_period',
            referenceId: $event->periodId,
        );
    }

    private function onEmployeeCreated(DomainEventMessage $message): void
    {
        /** @var EmployeeCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMPLOYEE_CREATED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Empleado creado',
            bodyTemplate: 'Se ha creado el empleado {{firstName}} {{lastName}}.',
            templateVars: [
                'employeeId' => $event->employeeId,
                'firstName' => $event->firstName,
                'lastName' => $event->lastName,
                'email' => $event->email,
            ],
            referenceType: 'employee',
            referenceId: $event->employeeId,
        );
    }

    private function onEmployeeDeactivated(DomainEventMessage $message): void
    {
        /** @var EmployeeDeactivated $event */
        $event = $message->restoreEvent();

        $recipients = [];
        if ($event->userId !== null) {
            $recipients[] = ['type' => RecipientType::USER, 'id' => $event->userId];
        }

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMPLOYEE_DEACTIVATED,
            recipients: $recipients,
            subject: 'Empleado desactivado',
            bodyTemplate: 'El empleado ha sido desactivado.',
            templateVars: [
                'employeeId' => $event->employeeId,
            ],
            referenceType: 'employee',
            referenceId: $event->employeeId,
        );
    }

    private function onEmployeeUserLinked(DomainEventMessage $message): void
    {
        /** @var EmployeeUserLinked $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMPLOYEE_USER_LINKED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Usuario vinculado a empleado',
            bodyTemplate: 'Se ha vinculado el usuario al empleado.',
            templateVars: [
                'employeeId' => $event->employeeId,
                'userId' => $event->userId,
            ],
            referenceType: 'employee',
            referenceId: $event->employeeId,
        );
    }

    private function onEmployeeUserUnlinked(DomainEventMessage $message): void
    {
        /** @var EmployeeUserUnlinked $event */
        $event = $message->restoreEvent();

        $recipients = [];
        if ($event->userId !== null) {
            $recipients[] = ['type' => RecipientType::USER, 'id' => $event->userId];
        }

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMPLOYEE_USER_UNLINKED,
            recipients: $recipients,
            subject: 'Usuario desvinculado de empleado',
            bodyTemplate: 'Se ha desvinculado el usuario del empleado. El acceso al sistema ha sido removido.',
            templateVars: [
                'employeeId' => $event->employeeId,
                'userId' => $event->userId ?? '',
            ],
            referenceType: 'employee',
            referenceId: $event->employeeId,
        );
    }

    private function onEmployeeAccessProvisioned(DomainEventMessage $message): void
    {
        /** @var EmployeeAccessProvisioned $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMPLOYEE_ACCESS_PROVISIONED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Acceso al sistema provisionado',
            bodyTemplate: 'Se ha creado y vinculado su cuenta de acceso al sistema.',
            templateVars: [
                'employeeId' => $event->employeeId,
                'userId' => $event->userId,
            ],
            referenceType: 'employee',
            referenceId: $event->employeeId,
        );
    }

    private function onVacationRequested(DomainEventMessage $message): void
    {
        /** @var VacationRequested $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::VACATION_REQUESTED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Vacaciones solicitadas',
            bodyTemplate: 'Se ha solicitado vacaciones del {{startDate}} al {{endDate}}.',
            templateVars: [
                'employeeId' => $event->employeeId,
                'startDate' => $event->startDate,
                'endDate' => $event->endDate,
            ],
            referenceType: 'vacation',
            referenceId: $event->employeeId,
        );
    }

    private function onVacationApproved(DomainEventMessage $message): void
    {
        /** @var VacationApproved $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::VACATION_APPROVED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Vacaciones aprobadas',
            bodyTemplate: 'Las vacaciones del {{startDate}} al {{endDate}} han sido aprobadas.',
            templateVars: [
                'employeeId' => $event->employeeId,
                'requestId' => $event->requestId,
                'startDate' => $event->startDate,
                'endDate' => $event->endDate,
            ],
            referenceType: 'vacation',
            referenceId: $event->requestId,
        );
    }

    private function onEmployeeReactivated(DomainEventMessage $message): void
    {
        /** @var EmployeeReactivated $event */
        $event = $message->restoreEvent();

        $recipients = [];
        if ($event->userId !== null) {
            $recipients[] = ['type' => RecipientType::USER, 'id' => $event->userId];
        }

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMPLOYEE_REACTIVATED,
            recipients: $recipients,
            subject: 'Empleado reactivado',
            bodyTemplate: 'El empleado ha sido reactivado.',
            templateVars: [
                'employeeId' => $event->employeeId,
            ],
            referenceType: 'employee',
            referenceId: $event->employeeId,
        );
    }

    private function onEmployeeDeleted(DomainEventMessage $message): void
    {
        /** @var EmployeeDeleted $event */
        $event = $message->restoreEvent();

        $recipients = [];
        if ($event->userId !== null) {
            $recipients[] = ['type' => RecipientType::USER, 'id' => $event->userId];
        }

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMPLOYEE_DELETED,
            recipients: $recipients,
            subject: 'Empleado eliminado',
            bodyTemplate: 'El empleado ha sido eliminado del sistema.',
            templateVars: [
                'employeeId' => $event->employeeId,
            ],
            referenceType: 'employee',
            referenceId: $event->employeeId,
        );
    }

    private function onVacationCancelled(DomainEventMessage $message): void
    {
        /** @var VacationCancelled $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::VACATION_CANCELLED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Vacaciones canceladas',
            bodyTemplate: 'La solicitud de vacaciones ha sido cancelada.',
            templateVars: [
                'employeeId' => $event->employeeId,
                'requestId' => $event->requestId,
            ],
            referenceType: 'vacation',
            referenceId: $event->requestId,
        );
    }

    private function onVacationRejected(DomainEventMessage $message): void
    {
        /** @var VacationRejected $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::VACATION_REJECTED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Vacaciones rechazadas',
            bodyTemplate: 'La solicitud de vacaciones ha sido rechazada.{{reason}}',
            templateVars: [
                'employeeId' => $event->employeeId,
                'requestId' => $event->requestId,
                'reason' => $event->reason !== '' ? ' Motivo: ' . $event->reason : '',
            ],
            referenceType: 'vacation',
            referenceId: $event->requestId,
        );
    }

    private function onOvertimeRegistered(DomainEventMessage $message): void
    {
        /** @var OvertimeRegistered $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::OVERTIME_REGISTERED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Horas extra registradas',
            bodyTemplate: 'Se han registrado {{totalHours}} horas extra para el {{workedAt}} ({{startTime}} - {{endTime}}).',
            templateVars: [
                'overtimeRecordId' => $event->overtimeRecordId,
                'employeeId' => $event->employeeId,
                'workedAt' => $event->workedAt,
                'startTime' => $event->startTime,
                'endTime' => $event->endTime,
                'totalHours' => (string) $event->totalHours,
            ],
            referenceType: 'overtime_record',
            referenceId: $event->overtimeRecordId,
        );
    }

    private function onOvertimeApproved(DomainEventMessage $message): void
    {
        /** @var OvertimeApproved $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::OVERTIME_APPROVED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Horas extra aprobadas',
            bodyTemplate: 'Las {{totalHours}} horas extra han sido aprobadas.',
            templateVars: [
                'overtimeRecordId' => $event->overtimeRecordId,
                'employeeId' => $event->employeeId,
                'approvedBy' => $event->approvedBy,
                'totalHours' => (string) $event->totalHours,
            ],
            referenceType: 'overtime_record',
            referenceId: $event->overtimeRecordId,
        );
    }

    private function onOvertimeCorrected(DomainEventMessage $message): void
    {
        /** @var OvertimeCorrected $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::OVERTIME_CORRECTED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Horas extra corregidas',
            bodyTemplate: 'Las horas extra del {{workedAt}} han sido corregidas ({{startTime}} - {{endTime}}).',
            templateVars: [
                'overtimeRecordId' => $event->overtimeRecordId,
                'employeeId' => $event->employeeId,
                'workedAt' => $event->workedAt,
                'startTime' => $event->startTime,
                'endTime' => $event->endTime,
            ],
            referenceType: 'overtime_record',
            referenceId: $event->overtimeRecordId,
        );
    }

    private function onOvertimeRejected(DomainEventMessage $message): void
    {
        /** @var OvertimeRejected $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::OVERTIME_REJECTED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Horas extra rechazadas',
            bodyTemplate: 'Las horas extra han sido rechazadas.{{reason}}',
            templateVars: [
                'overtimeRecordId' => $event->overtimeRecordId,
                'employeeId' => $event->employeeId,
                'rejectedBy' => $event->rejectedBy,
                'reason' => $event->reason !== '' ? ' Motivo: ' . $event->reason : '',
            ],
            referenceType: 'overtime_record',
            referenceId: $event->overtimeRecordId,
        );
    }

    private function onOvertimeReviewRequested(DomainEventMessage $message): void
    {
        /** @var OvertimeReviewRequested $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::OVERTIME_REVIEW_REQUESTED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Revisión de horas extra solicitada',
            bodyTemplate: 'Se ha solicitado una revisión de las horas extra.{{correctionHint}}',
            templateVars: [
                'overtimeRecordId' => $event->overtimeRecordId,
                'employeeId' => $event->employeeId,
                'reviewRequestedBy' => $event->reviewRequestedBy,
                'correctionHint' => $event->correctionHint !== null ? ' Sugerencia: ' . $event->correctionHint : '',
            ],
            referenceType: 'overtime_record',
            referenceId: $event->overtimeRecordId,
        );
    }

    private function onServiceZoneCreated(DomainEventMessage $message): void
    {
        /** @var ServiceZoneCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SERVICE_ZONE_CREATED,
            recipients: [],
            subject: 'Zona de servicio creada',
            bodyTemplate: 'Se ha creado la zona de servicio {{name}}.',
            templateVars: [
                'zoneId' => $event->zoneId,
                'name' => $event->name,
            ],
            referenceType: 'service_zone',
            referenceId: $event->zoneId,
        );
    }

    private function onServiceZoneDeactivated(DomainEventMessage $message): void
    {
        /** @var ServiceZoneDeactivated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SERVICE_ZONE_DEACTIVATED,
            recipients: [],
            subject: 'Zona de servicio desactivada',
            bodyTemplate: 'La zona de servicio ha sido desactivada.',
            templateVars: [
                'zoneId' => $event->zoneId,
            ],
            referenceType: 'service_zone',
            referenceId: $event->zoneId,
        );
    }

    private function onServiceZoneReactivated(DomainEventMessage $message): void
    {
        /** @var ServiceZoneReactivated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SERVICE_ZONE_REACTIVATED,
            recipients: [],
            subject: 'Zona de servicio reactivada',
            bodyTemplate: 'La zona de servicio ha sido reactivada.',
            templateVars: [
                'zoneId' => $event->zoneId,
            ],
            referenceType: 'service_zone',
            referenceId: $event->zoneId,
        );
    }

    private function onSpecialPeriodCancelled(DomainEventMessage $message): void
    {
        /** @var SpecialPeriodCancelled $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SPECIAL_PERIOD_CANCELLED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Período especial cancelado',
            bodyTemplate: 'El período especial ha sido cancelado.',
            templateVars: [
                'periodId' => $event->periodId,
                'employeeId' => $event->employeeId,
            ],
            referenceType: 'special_period',
            referenceId: $event->periodId,
        );
    }

    private function onSpecialPeriodCompleted(DomainEventMessage $message): void
    {
        /** @var SpecialPeriodCompleted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SPECIAL_PERIOD_COMPLETED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Período especial completado',
            bodyTemplate: 'El período especial ha sido completado.',
            templateVars: [
                'periodId' => $event->periodId,
                'employeeId' => $event->employeeId,
            ],
            referenceType: 'special_period',
            referenceId: $event->periodId,
        );
    }

    private function onSpecialPeriodEdited(DomainEventMessage $message): void
    {
        /** @var SpecialPeriodEdited $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SPECIAL_PERIOD_EDITED,
            recipients: [],
            subject: 'Período especial editado',
            bodyTemplate: 'El período especial ha sido editado.',
            templateVars: [
                'periodId' => $event->periodId,
            ],
            referenceType: 'special_period',
            referenceId: $event->periodId,
        );
    }

    private function onCityTransferred(DomainEventMessage $message): void
    {
        /** @var CityTransferred $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CITY_TRANSFERRED,
            recipients: [],
            subject: 'Ciudad transferida',
            bodyTemplate: 'La ciudad ha sido transferida de una zona de servicio a otra.',
            templateVars: [
                'cityId' => $event->cityId,
                'fromZoneId' => $event->fromZoneId,
                'toZoneId' => $event->toZoneId,
            ],
            referenceType: 'city',
            referenceId: $event->cityId,
        );
    }

    private function onStockBelowThreshold(DomainEventMessage $message): void
    {
        /** @var StockBelowThreshold $event */
        $event = $message->restoreEvent();

        // TODO: locationId es un ID de ubicación, no de empleado.
        //       Se necesita un LocationContactResolver para resolver el contacto
        //       responsable de la ubicación (vía Organization BC).
        $this->dispatcher->dispatchForEvent(
            eventType: EventType::STOCK_BELOW_THRESHOLD,
            recipients: [
                ['type' => RecipientType::LOCATION, 'id' => $event->locationId],
            ],
            subject: 'Stock por debajo del umbral',
            bodyTemplate: 'El producto ha alcanzado un nivel de stock por debajo del umbral mínimo en la ubicación.',
            templateVars: [
                'productId' => $event->productId,
                'locationId' => $event->locationId,
            ],
            referenceType: 'product',
            referenceId: $event->productId,
        );
    }

    private function onEmergencyPartUsed(DomainEventMessage $message): void
    {
        /** @var EmergencyPartUsed $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMERGENCY_PART_USED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Repuesto de emergencia usado',
            bodyTemplate: 'Se ha utilizado un repuesto de emergencia.{{notes}}',
            templateVars: [
                'productId' => $event->productId,
                'serializedStockId' => $event->serializedStockId,
                'notes' => $event->notes !== null ? ' Notas: ' . $event->notes : '',
            ],
            referenceType: 'serialized_stock',
            referenceId: $event->serializedStockId,
        );
    }

    private function onPartRepaired(DomainEventMessage $message): void
    {
        /** @var PartRepaired $event */
        $event = $message->restoreEvent();

        // PartRepaired only contains serializedStockId, no recipient data (e.g., assignedToUserId, technicianId).
        // Need to extend the event or resolve recipient from serializedStock to notify responsible technician.
        // For now, no notifications are dispatched.
        $this->dispatcher->dispatchForEvent(
            eventType: EventType::PART_REPAIRED,
            recipients: [],
            subject: 'Repuesto reparado',
            bodyTemplate: 'El repuesto ha sido reparado.',
            templateVars: [
                'serializedStockId' => $event->serializedStockId,
            ],
            referenceType: 'serialized_stock',
            referenceId: $event->serializedStockId,
        );
    }

    private function onRequestApproved(DomainEventMessage $message): void
    {
        /** @var RequestApproved $event */
        $event = $message->restoreEvent();

        // G-01: RequestApproved event has no requester field yet
        // (pending inventory domain event enrichment)
        $this->dispatcher->dispatchForEvent(
            eventType: EventType::REQUEST_APPROVED,
            recipients: [],
            subject: 'Solicitud aprobada',
            bodyTemplate: 'La solicitud de repuesto ha sido aprobada.',
            templateVars: [
                'requestId' => $event->requestId,
            ],
            referenceType: 'request',
            referenceId: $event->requestId,
        );
    }

    private function onStockMoved(DomainEventMessage $message): void
    {
        /** @var StockMoved $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::STOCK_MOVED,
            recipients: [],
            subject: 'Movimiento de stock',
            bodyTemplate: 'Se ha completado un movimiento de stock.',
            templateVars: [
                'movementId' => $event->movementId,
            ],
            referenceType: 'movement',
            referenceId: $event->movementId,
        );
    }

    private function onSerializedStockCreated(DomainEventMessage $message): void
    {
        /** @var SerializedStockCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SERIALIZED_STOCK_CREATED,
            recipients: [],
            subject: 'Stock serializado registrado',
            bodyTemplate: 'Se ha registrado un nuevo ítem de stock serializado.',
            templateVars: [
                'serializedStockId' => $event->serializedStockId,
            ],
            referenceType: 'serialized_stock',
            referenceId: $event->serializedStockId,
        );
    }

    private function onEntryConfirmed(DomainEventMessage $message): void
    {
        /** @var EntryConfirmed $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::ENTRY_CONFIRMED,
            recipients: [],
            subject: 'Entrada confirmada',
            bodyTemplate: 'Se ha confirmado una entrada de inventario.',
            templateVars: [
                'entryId' => $event->entryId,
            ],
            referenceType: 'entry',
            referenceId: $event->entryId,
        );
    }

    private function onProductCreated(DomainEventMessage $message): void
    {
        /** @var ProductCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::PRODUCT_CREATED,
            recipients: [],
            subject: 'Producto creado',
            bodyTemplate: 'Se ha creado un nuevo producto en el catálogo. Código: {{productCode}}.',
            templateVars: [
                'productId' => $event->productId,
                'productCode' => $event->productCode,
            ],
            referenceType: 'product',
            referenceId: $event->productId,
        );
    }

    private function onRequestSubmitted(DomainEventMessage $message): void
    {
        /** @var RequestSubmitted $event */
        $event = $message->restoreEvent();

        $recipients = [];
        if ($event->sourceResponsibleUserId !== null) {
            $recipients[] = ['type' => RecipientType::USER, 'id' => $event->sourceResponsibleUserId];
        }
        // También notificar al solicitante como confirmación
        $recipients[] = ['type' => RecipientType::USER, 'id' => $event->requestedByUserId];

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::REQUEST_SUBMITTED,
            recipients: $recipients,
            subject: 'Nueva solicitud: ' . $event->reference,
            bodyTemplate: 'Se ha creado una solicitud de repuesto. Referencia: {{reference}}.',
            templateVars: [
                'requestId' => $event->requestId,
                'reference' => $event->reference,
                'productId' => $event->productId,
                'quantity' => (string) $event->quantity,
            ],
            referenceType: 'request',
            referenceId: $event->requestId,
        );
    }

    private function onRequestAwaitingTechnicianAcceptance(DomainEventMessage $message): void
    {
        /** @var RequestAwaitingTechnicianAcceptance $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::REQUEST_AWAITING_TECHNICIAN_ACCEPTANCE,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->requestedByUserId],
            ],
            subject: 'Solicitud aprobada: ' . $event->reference,
            bodyTemplate: 'Tu solicitud {{reference}} ha sido aprobada. Debes aceptar el traslado.',
            templateVars: [
                'requestId' => $event->requestId,
                'reference' => $event->reference,
            ],
            referenceType: 'request',
            referenceId: $event->requestId,
        );
    }

    private function onRequestRejected(DomainEventMessage $message): void
    {
        /** @var RequestRejected $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::REQUEST_REJECTED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->requestedByUserId],
            ],
            subject: 'Solicitud rechazada: ' . $event->reference,
            bodyTemplate: 'Tu solicitud {{reference}} ha sido rechazada.',
            templateVars: [
                'requestId' => $event->requestId,
                'reference' => $event->reference,
            ],
            referenceType: 'request',
            referenceId: $event->requestId,
        );
    }

    private function onRequestCancelled(DomainEventMessage $message): void
    {
        /** @var RequestCancelled $event */
        $event = $message->restoreEvent();

        // Notificar al técnico solicitante que su solicitud fue cancelada
        $this->dispatcher->dispatchForEvent(
            eventType: EventType::REQUEST_CANCELLED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->requestedByUserId],
            ],
            subject: 'Solicitud cancelada: ' . $event->reference,
            bodyTemplate: 'La solicitud {{reference}} ha sido cancelada.',
            templateVars: [
                'requestId' => $event->requestId,
                'reference' => $event->reference,
            ],
            referenceType: 'request',
            referenceId: $event->requestId,
        );
    }

    private function onRequestFulfilled(DomainEventMessage $message): void
    {
        /** @var RequestFulfilled $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::REQUEST_FULFILLED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->requestedByUserId],
            ],
            subject: 'Solicitud ejecutada: ' . $event->reference,
            bodyTemplate: 'Tu solicitud {{reference}} ha sido ejecutada. El stock ha sido transferido.',
            templateVars: [
                'requestId' => $event->requestId,
                'reference' => $event->reference,
            ],
            referenceType: 'request',
            referenceId: $event->requestId,
        );
    }

    private function onUserRegistered(DomainEventMessage $message): void
    {
        /** @var UserRegistered $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::USER_REGISTERED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Usuario registrado',
            bodyTemplate: 'El usuario {{username}} ha sido registrado exitosamente.',
            templateVars: [
                'userId' => $event->userId,
                'username' => $event->username,
            ],
            referenceType: 'user',
            referenceId: $event->userId,
        );
    }

    private function onUserAccountActivated(DomainEventMessage $message): void
    {
        /** @var UserAccountActivated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::USER_ACCOUNT_ACTIVATED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Cuenta activada',
            bodyTemplate: 'Su cuenta de usuario {{username}} ha sido activada.',
            templateVars: [
                'userId' => $event->userId,
                'username' => $event->username,
            ],
            referenceType: 'user',
            referenceId: $event->userId,
        );
    }

    private function onPasswordResetCompleted(DomainEventMessage $message): void
    {
        /** @var PasswordResetCompleted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::USER_PASSWORD_RESET,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Contraseña restablecida',
            bodyTemplate: 'Su contraseña ha sido restablecida exitosamente.',
            templateVars: [
                'userId' => $event->userId,
            ],
            referenceType: 'user',
            referenceId: $event->userId,
        );
    }

    private function onExpenseItemRejected(DomainEventMessage $message): void
    {
        /** @var ExpenseItemRejected $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EXPENSE_ITEM_REJECTED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->reviewedBy],
            ],
            subject: 'Gasto rechazado',
            bodyTemplate: 'Un item de gasto ha sido rechazado. Motivo: {{rejectionReason}}.',
            templateVars: [
                'expenseId' => $event->expenseId,
                'itemId' => $event->itemId,
                'rejectionReason' => $event->rejectionReason,
            ],
            referenceType: 'expense',
            referenceId: $event->expenseId,
        );
    }

    private function onExpenseApproved(DomainEventMessage $message): void
    {
        /** @var ExpenseApproved $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EXPENSE_APPROVED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->approvedBy],
            ],
            subject: 'Liquidación aprobada',
            bodyTemplate: 'La liquidación ha sido aprobada por un monto de {{totalAmount}}.',
            templateVars: [
                'expenseId' => $event->expenseId,
                'totalAmount' => (string) $event->totalAmount,
            ],
            referenceType: 'expense',
            referenceId: $event->expenseId,
        );
    }

    private function onExpenseSubmitted(DomainEventMessage $message): void
    {
        /** @var ExpenseSubmitted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EXPENSE_SUBMITTED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->employeeId],
            ],
            subject: 'Liquidación enviada',
            bodyTemplate: 'Su liquidación ha sido enviada para revisión. Total: {{totalAmount}} ({{totalItems}} items).',
            templateVars: [
                'expenseId' => $event->expenseId,
                'totalAmount' => (string) $event->totalAmount,
                'totalItems' => (string) $event->totalItems,
            ],
            referenceType: 'expense',
            referenceId: $event->expenseId,
        );
    }

    private function onExpenseResubmitted(DomainEventMessage $message): void
    {
        /** @var ExpenseResubmitted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EXPENSE_RESUBMITTED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->employeeId],
            ],
            subject: 'Liquidación reenviada',
            bodyTemplate: 'Su liquidación ha sido reenviada para revisión. Total: {{totalAmount}} ({{totalItems}} items).',
            templateVars: [
                'expenseId' => $event->expenseId,
                'totalAmount' => (string) $event->totalAmount,
                'totalItems' => (string) $event->totalItems,
            ],
            referenceType: 'expense',
            referenceId: $event->expenseId,
        );
    }

    private function onCashAdvanceCreated(DomainEventMessage $message): void
    {
        /** @var CashAdvanceCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CASH_ADVANCE_CREATED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Adelanto creado',
            bodyTemplate: 'Se ha creado un adelanto de {{amount}} a su nombre. N° {{advanceNumber}}.',
            templateVars: [
                'cashAdvanceId' => $event->cashAdvanceId,
                'amount' => (string) $event->amount,
                'advanceNumber' => $event->advanceNumber,
            ],
            referenceType: 'cash_advance',
            referenceId: $event->cashAdvanceId,
        );
    }

    private function onCashAdvanceUpdated(DomainEventMessage $message): void
    {
        /** @var CashAdvanceUpdated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CASH_ADVANCE_UPDATED,
            recipients: [],
            subject: 'Adelanto actualizado',
            bodyTemplate: 'El adelanto #{{cashAdvanceId}} ha sido actualizado de {{previousAmount}} a {{newAmount}}.',
            templateVars: [
                'cashAdvanceId' => $event->cashAdvanceId,
                'previousAmount' => (string) $event->previousAmount,
                'newAmount' => (string) $event->newAmount,
            ],
            referenceType: 'cash_advance',
            referenceId: $event->cashAdvanceId,
        );
    }

    private function onCashAdvancePaymentPending(DomainEventMessage $message): void
    {
        /** @var CashAdvancePaymentPending $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CASH_ADVANCE_PENDING_PAYMENT,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->employeeId],
            ],
            subject: 'Adelanto pendiente de pago',
            bodyTemplate: 'Tiene un adelanto de {{amount}} pendiente de pago desde hace {{daysSinceGiven}} días.',
            templateVars: [
                'cashAdvanceId' => $event->cashAdvanceId,
                'amount' => (string) $event->amount,
                'daysSinceGiven' => (string) $event->daysSinceGiven,
            ],
            referenceType: 'cash_advance',
            referenceId: $event->cashAdvanceId,
        );
    }

    private function onCashAdvancePendingReview(DomainEventMessage $message): void
    {
        /** @var CashAdvancePendingReview $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CASH_ADVANCE_PENDING_REVIEW,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->givenBy],
            ],
            subject: 'Adelanto pendiente de revisión financiera',
            bodyTemplate: 'El adelanto #{{cashAdvanceId}} de {{amount}} del empleado {{employeeId}} está pendiente de revisión.',
            templateVars: [
                'cashAdvanceId' => $event->cashAdvanceId,
                'employeeId' => $event->employeeId,
                'amount' => (string) $event->amount,
            ],
            referenceType: 'cash_advance',
            referenceId: $event->cashAdvanceId,
        );
    }

    private function onCashAdvanceSettled(DomainEventMessage $message): void
    {
        /** @var CashAdvanceSettled $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CASH_ADVANCE_SETTLED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->confirmedBy],
            ],
            subject: 'Adelanto liquidado',
            bodyTemplate: 'El adelanto de {{amount}} ha sido liquidado. Dirección: {{direction}}.',
            templateVars: [
                'cashAdvanceId' => $event->cashAdvanceId,
                'amount' => (string) $event->amount,
                'direction' => $event->direction,
            ],
            referenceType: 'cash_advance',
            referenceId: $event->cashAdvanceId,
        );
    }

    private function onTicketCreated(DomainEventMessage $message): void
    {
        /** @var TicketCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_CREATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Ticket creado: ' . $event->ticketNumber,
            bodyTemplate: 'Se ha creado el ticket {{ticketNumber}} para el servicio {{serviceType}}.',
            templateVars: [
                'ticketNumber' => $event->ticketNumber,
                'serviceType' => $event->serviceType,
                'ticketId' => $event->ticketId,
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketAssigned(DomainEventMessage $message): void
    {
        /** @var TicketAssigned $event */
        $event = $message->restoreEvent();

        $recipients = [];
        foreach ($event->employeeIds as $empId) {
            $recipients[] = ['type' => RecipientType::EMPLOYEE, 'id' => $empId];
        }

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_ASSIGNED,
            recipients: $recipients,
            subject: 'Ticket asignado',
            bodyTemplate: 'Se le ha asignado un ticket. Fecha programada: {{scheduledAt}}.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'scheduledAt' => $event->scheduledAt?->format('Y-m-d H:i') ?? 'No programado',
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketReassigned(DomainEventMessage $message): void
    {
        /** @var TicketReassigned $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_REASSIGNED,
            recipients: [
                ['type' => RecipientType::EMPLOYEE, 'id' => $event->newEmployeeId],
            ],
            subject: 'Ticket reasignado',
            bodyTemplate: 'Se le ha reasignado un ticket previamente asignado a otro técnico.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'previousEmployeeId' => $event->previousEmployeeId,
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketRescheduled(DomainEventMessage $message): void
    {
        /** @var TicketRescheduled $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_RESCHEDULED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->requestedBy],
            ],
            subject: 'Ticket reprogramado',
            bodyTemplate: 'El ticket ha sido reprogramado de {{previousDate}} a {{newDate}}. Motivo: {{reason}}.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'previousDate' => $event->previousScheduledAt->format('Y-m-d H:i'),
                'newDate' => $event->newScheduledAt->format('Y-m-d H:i'),
                'reason' => $event->reason,
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketCancelled(DomainEventMessage $message): void
    {
        /** @var TicketCancelled $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_CANCELLED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->cancelledBy],
            ],
            subject: 'Ticket cancelado',
            bodyTemplate: 'El ticket ha sido cancelado. Motivo: {{reason}}.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'reason' => $event->reason,
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketClosed(DomainEventMessage $message): void
    {
        /** @var TicketClosed $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_CLOSED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->approvedBy],
            ],
            subject: 'Ticket cerrado',
            bodyTemplate: 'El ticket ha sido cerrado. Código CAT: {{catCode}}.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'catCode' => $event->catCode,
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketArrivedAtSite(DomainEventMessage $message): void
    {
        /** @var TicketArrivedAtSite $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_ARRIVED_AT_SITE,
            recipients: [],
            subject: 'Llegada al sitio',
            bodyTemplate: 'El técnico ha llegado al sitio del ticket.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'arrivedAt' => $event->arrivedAt->format('Y-m-d H:i'),
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketAssistanceStarted(DomainEventMessage $message): void
    {
        /** @var TicketAssistanceStarted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_ASSISTANCE_STARTED,
            recipients: [],
            subject: 'Asistencia iniciada',
            bodyTemplate: 'La asistencia técnica ha iniciado en el ticket.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'startedAt' => $event->startedAt->format('Y-m-d H:i'),
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketAssistanceCompleted(DomainEventMessage $message): void
    {
        /** @var TicketAssistanceCompleted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_ASSISTANCE_COMPLETED,
            recipients: [],
            subject: 'Asistencia completada',
            bodyTemplate: 'La asistencia técnica ha sido completada.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'completedAt' => $event->completedAt->format('Y-m-d H:i'),
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketFindingRegistered(DomainEventMessage $message): void
    {
        /** @var TicketFindingRegistered $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_FINDING_REGISTERED,
            recipients: [],
            subject: 'Hallazgo registrado',
            bodyTemplate: 'Se ha registrado un hallazgo en el ticket. Tipo de problema: {{problemTypeFound}}.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'findingId' => $event->findingId,
                'problemTypeFound' => $event->problemTypeFound,
            ],
            referenceType: 'finding',
            referenceId: $event->findingId,
        );
    }

    private function onTicketJourneyStarted(DomainEventMessage $message): void
    {
        /** @var TicketJourneyStarted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_JOURNEY_STARTED,
            recipients: [],
            subject: 'Viaje iniciado',
            bodyTemplate: 'Se ha iniciado un viaje de tipo {{journeyType}} para el ticket.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'journeyType' => $event->journeyType,
                'startedAt' => $event->startedAt->format('Y-m-d H:i'),
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketPaused(DomainEventMessage $message): void
    {
        /** @var TicketPaused $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_PAUSED,
            recipients: [],
            subject: 'Ticket pausado',
            bodyTemplate: 'El ticket ha sido pausado. Motivo: {{reason}}.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'reason' => $event->reason,
                'pausedBy' => $event->pausedBy,
                'pausedAt' => $event->pausedAt->format('Y-m-d H:i'),
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketWaitingStarted(DomainEventMessage $message): void
    {
        /** @var TicketWaitingStarted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_WAITING_STARTED,
            recipients: [],
            subject: 'Espera iniciada',
            bodyTemplate: 'Se ha iniciado un período de espera en el ticket.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'startedAt' => $event->startedAt->format('Y-m-d H:i'),
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketResumed(DomainEventMessage $message): void
    {
        /** @var TicketResumed $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_RESUMED,
            recipients: [],
            subject: 'Ticket reanudado',
            bodyTemplate: 'El ticket ha sido reanudado.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'resumedAt' => $event->resumedAt->format('Y-m-d H:i'),
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketReopenRequested(DomainEventMessage $message): void
    {
        /** @var TicketReopenRequested $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_REOPEN_REQUESTED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->requestedBy],
            ],
            subject: 'Solicitud de reapertura',
            bodyTemplate: 'Se ha solicitado la reapertura del ticket. Motivo: {{reason}}.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'reason' => $event->reason,
                'requestedBy' => $event->requestedBy,
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onTicketReopened(DomainEventMessage $message): void
    {
        /** @var TicketReopened $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TICKET_REOPENED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->approvedBy],
            ],
            subject: 'Ticket reabierto',
            bodyTemplate: 'El ticket ha sido reabierto. Motivo: {{reason}}.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'reason' => $event->reason,
                'approvedBy' => $event->approvedBy,
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onSlaAboutToExpire(DomainEventMessage $message): void
    {
        /** @var SlaAboutToExpire $event */
        $event = $message->restoreEvent();

        $recipients = [];
        if ($event->inChargeEmployeeId !== '') {
            $recipients[] = ['type' => RecipientType::EMPLOYEE, 'id' => $event->inChargeEmployeeId];
        }
        foreach ($event->assignedEmployeeIds as $empId) {
            if ($empId !== $event->inChargeEmployeeId) {
                $recipients[] = ['type' => RecipientType::EMPLOYEE, 'id' => $empId];
            }
        }

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SLA_ABOUT_TO_EXPIRE,
            recipients: $recipients,
            subject: 'SLA próximo a vencer: ' . $event->ticketNumber,
            bodyTemplate: 'El ticket {{ticketNumber}} tiene un SLA próximo a vencer (deadline: {{slaDeadline}}).',
            templateVars: [
                'ticketId' => $event->ticketId,
                'ticketNumber' => $event->ticketNumber,
                'slaDeadline' => $event->slaDeadlineAt->format('Y-m-d H:i'),
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onSlaBreached(DomainEventMessage $message): void
    {
        /** @var SlaBreached $event */
        $event = $message->restoreEvent();

        $recipients = [];
        if ($event->inChargeEmployeeId !== '') {
            $recipients[] = ['type' => RecipientType::EMPLOYEE, 'id' => $event->inChargeEmployeeId];
        }
        foreach ($event->assignedEmployeeIds as $empId) {
            if ($empId !== $event->inChargeEmployeeId) {
                $recipients[] = ['type' => RecipientType::EMPLOYEE, 'id' => $empId];
            }
        }

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SLA_BREACHED,
            recipients: $recipients,
            subject: 'SLA vencido: ' . $event->ticketNumber,
            bodyTemplate: 'El SLA del ticket {{ticketNumber}} ha vencido (deadline era: {{slaDeadline}}). Se requiere atención inmediata.',
            templateVars: [
                'ticketId' => $event->ticketId,
                'ticketNumber' => $event->ticketNumber,
                'slaDeadline' => $event->slaDeadlineAt->format('Y-m-d H:i'),
            ],
            referenceType: 'ticket',
            referenceId: $event->ticketId,
        );
    }

    private function onClientCreated(DomainEventMessage $message): void
    {
        /** @var ClientCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CLIENT_CREATED,
            recipients: [],
            subject: 'Cliente creado',
            bodyTemplate: 'Se ha creado el cliente {{clientName}}.',
            templateVars: [
                'clientId' => $event->clientId,
                'clientName' => $event->clientName,
            ],
            referenceType: 'client',
            referenceId: $event->clientId,
        );
    }

    private function onContactAdded(DomainEventMessage $message): void
    {
        /** @var ContactAdded $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTACT_ADDED,
            recipients: [],
            subject: 'Contacto agregado',
            bodyTemplate: 'Se ha agregado un nuevo contacto al cliente.',
            templateVars: [
                'contactId' => $event->contactId,
                'clientId' => $event->clientId,
            ],
            referenceType: 'contact',
            referenceId: $event->contactId,
        );
    }

    private function onClientUpdated(DomainEventMessage $message): void
    {
        /** @var ClientUpdated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CLIENT_UPDATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Cliente actualizado',
            bodyTemplate: 'El cliente {{clientName}} ha sido actualizado.',
            templateVars: [
                'clientId' => $event->clientId,
                'clientName' => $event->clientName,
            ],
            referenceType: 'client',
            referenceId: $event->clientId,
        );
    }

    private function onClientSaleOrderCreated(DomainEventMessage $message): void
    {
        /** @var ClientSaleOrderCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CLIENT_SALE_ORDER_CREATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Orden de venta creada',
            bodyTemplate: 'Se ha creado una nueva orden de venta para el cliente.',
            templateVars: [
                'orderId' => $event->orderId,
                'clientId' => $event->clientId,
            ],
            referenceType: 'sale_order',
            referenceId: $event->orderId,
        );
    }

    private function onClientSaleOrderCompleted(DomainEventMessage $message): void
    {
        /** @var ClientSaleOrderCompleted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CLIENT_SALE_ORDER_COMPLETED,
            recipients: [],
            subject: 'Orden de venta completada',
            bodyTemplate: 'La orden de venta ha sido completada.',
            templateVars: [
                'orderId' => $event->orderId,
            ],
            referenceType: 'sale_order',
            referenceId: $event->orderId,
        );
    }

    private function onContactUpdated(DomainEventMessage $message): void
    {
        /** @var ContactUpdated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTACT_UPDATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Contacto actualizado',
            bodyTemplate: 'Un contacto del cliente ha sido actualizado.',
            templateVars: [
                'contactId' => $event->contactId,
                'clientId' => $event->clientId,
            ],
            referenceType: 'contact',
            referenceId: $event->contactId,
        );
    }

    private function onContactDeactivated(DomainEventMessage $message): void
    {
        /** @var ContactDeactivated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTACT_DEACTIVATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Contacto desactivado',
            bodyTemplate: 'Un contacto del cliente ha sido desactivado.',
            templateVars: [
                'contactId' => $event->contactId,
                'clientId' => $event->clientId,
            ],
            referenceType: 'contact',
            referenceId: $event->contactId,
        );
    }

    private function onContactReactivated(DomainEventMessage $message): void
    {
        /** @var ContactReactivated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTACT_REACTIVATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Contacto reactivado',
            bodyTemplate: 'Un contacto del cliente ha sido reactivado.',
            templateVars: [
                'contactId' => $event->contactId,
                'clientId' => $event->clientId,
            ],
            referenceType: 'contact',
            referenceId: $event->contactId,
        );
    }

    private function onContactUserLinked(DomainEventMessage $message): void
    {
        /** @var ContactUserLinked $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTACT_USER_LINKED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Contacto vinculado a usuario',
            bodyTemplate: 'El contacto ha sido vinculado a una cuenta de usuario.',
            templateVars: [
                'contactId' => $event->contactId,
                'userId' => $event->userId,
            ],
            referenceType: 'contact',
            referenceId: $event->contactId,
        );
    }

    private function onContactUserUnlinked(DomainEventMessage $message): void
    {
        /** @var ContactUserUnlinked $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTACT_USER_UNLINKED,
            recipients: [],
            subject: 'Contacto desvinculado de usuario',
            bodyTemplate: 'El contacto ha sido desvinculado de su cuenta de usuario.',
            templateVars: [
                'contactId' => $event->contactId,
            ],
            referenceType: 'contact',
            referenceId: $event->contactId,
        );
    }

    private function onContactAccessProvisioned(DomainEventMessage $message): void
    {
        /** @var ContactAccessProvisioned $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTACT_ACCESS_PROVISIONED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Acceso de contacto provisionado',
            bodyTemplate: 'Se ha creado y vinculado una cuenta de acceso al portal de clientes.',
            templateVars: [
                'contactId' => $event->contactId,
                'clientId' => $event->clientId,
                'userId' => $event->userId,
            ],
            referenceType: 'contact',
            referenceId: $event->contactId,
        );
    }

    private function onTerminalRegistered(DomainEventMessage $message): void
    {
        /** @var TerminalRegistered $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TERMINAL_REGISTERED,
            recipients: [],
            subject: 'Terminal registrada',
            bodyTemplate: 'Se ha registrado una nueva terminal. Serial: {{serialNumber}}.',
            templateVars: [
                'terminalId' => $event->terminalId,
                'serialNumber' => $event->serialNumber,
            ],
            referenceType: 'terminal',
            referenceId: $event->terminalId,
        );
    }

    private function onTerminalUpdated(DomainEventMessage $message): void
    {
        /** @var TerminalUpdated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TERMINAL_UPDATED,
            recipients: [],
            subject: 'Terminal actualizada',
            bodyTemplate: 'La terminal {{serialNumber}} ha sido actualizada.',
            templateVars: [
                'terminalId' => $event->terminalId,
                'serialNumber' => $event->serialNumber,
            ],
            referenceType: 'terminal',
            referenceId: $event->terminalId,
        );
    }

    private function onTerminalDeactivated(DomainEventMessage $message): void
    {
        /** @var TerminalDeactivated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TERMINAL_DEACTIVATED,
            recipients: [],
            subject: 'Terminal desactivada',
            bodyTemplate: 'La terminal ha sido desactivada.',
            templateVars: [
                'terminalId' => $event->terminalId,
            ],
            referenceType: 'terminal',
            referenceId: $event->terminalId,
        );
    }

    private function onTerminalReactivated(DomainEventMessage $message): void
    {
        /** @var TerminalReactivated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TERMINAL_REACTIVATED,
            recipients: [],
            subject: 'Terminal reactivada',
            bodyTemplate: 'La terminal ha sido reactivada.',
            templateVars: [
                'terminalId' => $event->terminalId,
            ],
            referenceType: 'terminal',
            referenceId: $event->terminalId,
        );
    }

    private function onTerminalAssignmentRegistered(DomainEventMessage $message): void
    {
        /** @var TerminalAssignmentRegistered $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TERMINAL_ASSIGNMENT_REGISTERED,
            recipients: [],
            subject: 'Asignación de terminal registrada',
            bodyTemplate: 'Se ha registrado una nueva asignación de terminal.',
            templateVars: [
                'assignmentId' => $event->assignmentId,
                'terminalId' => $event->terminalId,
            ],
            referenceType: 'terminal_assignment',
            referenceId: $event->assignmentId,
        );
    }

    private function onTerminalAssignmentUpdated(DomainEventMessage $message): void
    {
        /** @var TerminalAssignmentUpdated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TERMINAL_ASSIGNMENT_UPDATED,
            recipients: [],
            subject: 'Asignación de terminal actualizada',
            bodyTemplate: 'La asignación de terminal ha sido actualizada.',
            templateVars: [
                'assignmentId' => $event->assignmentId,
                'terminalId' => $event->terminalId,
            ],
            referenceType: 'terminal_assignment',
            referenceId: $event->assignmentId,
        );
    }

    private function onTerminalAssignmentDeactivated(DomainEventMessage $message): void
    {
        /** @var TerminalAssignmentDeactivated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TERMINAL_ASSIGNMENT_DEACTIVATED,
            recipients: [],
            subject: 'Asignación de terminal desactivada',
            bodyTemplate: 'La asignación de terminal ha sido desactivada.',
            templateVars: [
                'assignmentId' => $event->assignmentId,
                'terminalId' => $event->terminalId,
            ],
            referenceType: 'terminal_assignment',
            referenceId: $event->assignmentId,
        );
    }

    private function onTerminalAssignmentReactivated(DomainEventMessage $message): void
    {
        /** @var TerminalAssignmentReactivated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::TERMINAL_ASSIGNMENT_REACTIVATED,
            recipients: [],
            subject: 'Asignación de terminal reactivada',
            bodyTemplate: 'La asignación de terminal ha sido reactivada.',
            templateVars: [
                'assignmentId' => $event->assignmentId,
                'terminalId' => $event->terminalId,
            ],
            referenceType: 'terminal_assignment',
            referenceId: $event->assignmentId,
        );
    }

    private function onContractCreated(DomainEventMessage $message): void
    {
        /** @var ContractCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTRACT_CREATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Contrato creado',
            bodyTemplate: 'Se ha creado un nuevo contrato para el cliente.',
            templateVars: [
                'contractId' => $event->contractId,
                'clientId' => $event->clientId,
            ],
            referenceType: 'contract',
            referenceId: $event->contractId,
        );
    }

    private function onContractUpdated(DomainEventMessage $message): void
    {
        /** @var ContractUpdated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTRACT_UPDATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Contrato actualizado',
            bodyTemplate: 'El contrato del cliente ha sido actualizado.',
            templateVars: [
                'contractId' => $event->contractId,
                'clientId' => $event->clientId,
            ],
            referenceType: 'contract',
            referenceId: $event->contractId,
        );
    }

    private function onContractCancelled(DomainEventMessage $message): void
    {
        /** @var ContractCancelled $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTRACT_CANCELLED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Contrato cancelado',
            bodyTemplate: 'El contrato del cliente ha sido cancelado.',
            templateVars: [
                'contractId' => $event->contractId,
                'clientId' => $event->clientId,
            ],
            referenceType: 'contract',
            referenceId: $event->contractId,
        );
    }

    private function onWarrantyRegistered(DomainEventMessage $message): void
    {
        /** @var WarrantyRegistered $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::WARRANTY_REGISTERED,
            recipients: [],
            subject: 'Garantía registrada',
            bodyTemplate: 'Se ha registrado una garantía de tipo {{type}} para la terminal.',
            templateVars: [
                'warrantyId' => $event->warrantyId,
                'terminalId' => $event->terminalId,
                'type' => $event->type,
            ],
            referenceType: 'warranty',
            referenceId: $event->warrantyId,
        );
    }

    private function onWarrantyUpdated(DomainEventMessage $message): void
    {
        /** @var WarrantyUpdated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::WARRANTY_UPDATED,
            recipients: [],
            subject: 'Garantía actualizada',
            bodyTemplate: 'La garantía de la terminal ha sido actualizada.',
            templateVars: [
                'warrantyId' => $event->warrantyId,
                'terminalId' => $event->terminalId,
            ],
            referenceType: 'warranty',
            referenceId: $event->warrantyId,
        );
    }

    private function onWarrantyExpired(DomainEventMessage $message): void
    {
        /** @var WarrantyExpired $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::WARRANTY_EXPIRED,
            recipients: [],
            subject: 'Garantía vencida',
            bodyTemplate: 'La garantía de la terminal ha vencido.',
            templateVars: [
                'warrantyId' => $event->warrantyId,
                'terminalId' => $event->terminalId,
            ],
            referenceType: 'warranty',
            referenceId: $event->warrantyId,
        );
    }

    private function onSlaPolicyCreated(DomainEventMessage $message): void
    {
        /** @var SlaPolicyCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::SLA_POLICY_CREATED,
            recipients: [],
            subject: 'Política de SLA creada',
            bodyTemplate: 'Se ha creado una nueva política de SLA para el contrato.',
            templateVars: [
                'policyId' => $event->policyId,
                'contractId' => $event->contractId,
            ],
            referenceType: 'sla_policy',
            referenceId: $event->policyId,
        );
    }

    private function onClientDeactivated(DomainEventMessage $message): void
    {
        /** @var ClientDeactivated $event */
        $event = $message->restoreEvent();

        $clientId = new ClientIdValueObject($event->clientId);

        // Cascade: desactivar todos los contactos activos del cliente
        $contacts = $this->contactRepository->findByClientId($clientId);
        foreach ($contacts as $contact) {
            try {
                ($this->deactivateContact)(new DeactivateContactCommand(
                    contactId: (string) $contact->id(),
                ));
            } catch (\Throwable $e) {
                // Si es el último contacto activo, el handler lanza excepción.
                // En el contexto de desactivación del cliente, no es blocking.
                $this->logger->warning('Could not deactivate contact {contactId} for client {clientId}: {message}', [
                    'contactId' => (string) $contact->id(),
                    'clientId' => $event->clientId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Notificar a los contactos del cliente sobre la desactivación
        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CLIENT_DEACTIVATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Cliente desactivado',
            bodyTemplate: 'El cliente ha sido desactivado.{{reason}}',
            templateVars: [
                'clientId' => $event->clientId,
                'reason' => $event->reason !== '' ? ' Motivo: ' . $event->reason : '',
            ],
            referenceType: 'client',
            referenceId: $event->clientId,
        );
    }

    private function onClientReactivated(DomainEventMessage $message): void
    {
        /** @var ClientReactivated $event */
        $event = $message->restoreEvent();

        $clientId = new ClientIdValueObject($event->clientId);

        // Cascade: reactivar todos los contactos inactivos del cliente
        $contacts = $this->contactRepository->findByClientId($clientId);
        foreach ($contacts as $contact) {
            if ($contact->isActive() || $contact->isDeleted()) {
                continue;
            }
            try {
                ($this->reactivateContact)(new ReactivateContactCommand(
                    contactId: (string) $contact->id(),
                ));
            } catch (\Throwable $e) {
                $this->logger->warning('Could not reactivate contact {contactId} for client {clientId}: {message}', [
                    'contactId' => (string) $contact->id(),
                    'clientId' => $event->clientId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Notificar a los contactos del cliente sobre la reactivación
        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CLIENT_REACTIVATED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Cliente reactivado',
            bodyTemplate: 'El cliente ha sido reactivado.',
            templateVars: [
                'clientId' => $event->clientId,
            ],
            referenceType: 'client',
            referenceId: $event->clientId,
        );
    }

    private function onClientDeleted(DomainEventMessage $message): void
    {
        /** @var ClientDeleted $event */
        $event = $message->restoreEvent();

        $clientId = new ClientIdValueObject($event->clientId);

        // Cascade: soft-delete todos los contactos del cliente
        $contacts = $this->contactRepository->findByClientId($clientId);
        foreach ($contacts as $contact) {
            try {
                ($this->softDeleteContact)(new SoftDeleteContactCommand(
                    contactId: (string) $contact->id(),
                    reason: $event->reason,
                ));
            } catch (\Throwable $e) {
                $this->logger->warning('Could not soft-delete contact {contactId} for client {clientId}: {message}', [
                    'contactId' => (string) $contact->id(),
                    'clientId' => $event->clientId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Notificar sobre la eliminación del cliente
        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CLIENT_DELETED,
            recipients: [
                ['type' => RecipientType::CONTACT, 'id' => $event->clientId],
            ],
            subject: 'Cliente eliminado',
            bodyTemplate: 'El cliente ha sido eliminado.{{reason}}',
            templateVars: [
                'clientId' => $event->clientId,
                'reason' => $event->reason !== '' ? ' Motivo: ' . $event->reason : '',
            ],
            referenceType: 'client',
            referenceId: $event->clientId,
        );
    }

    private function onUserUnlocked(DomainEventMessage $message): void
    {
        /** @var UserUnlocked $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::USER_UNLOCKED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Cuenta desbloqueada',
            bodyTemplate: 'Su cuenta de usuario ha sido desbloqueada. Ya puede iniciar sesión.',
            templateVars: [
                'userId' => $event->userId,
            ],
            referenceType: 'user',
            referenceId: $event->userId,
        );
    }

    private function onPasswordChanged(DomainEventMessage $message): void
    {
        /** @var PasswordChanged $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::PASSWORD_CHANGED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Contraseña cambiada',
            bodyTemplate: 'Su contraseña ha sido cambiada exitosamente. Si no realizó este cambio, contacte al administrador.',
            templateVars: [
                'userId' => $event->userId,
            ],
            referenceType: 'user',
            referenceId: $event->userId,
        );
    }

    private function onPasswordResetRequested(DomainEventMessage $message): void
    {
        /** @var PasswordResetRequested $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::PASSWORD_RESET_REQUESTED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Solicitud de restablecimiento de contraseña',
            bodyTemplate: 'Se ha solicitado el restablecimiento de su contraseña. Revise su correo para continuar.',
            templateVars: [
                'userId' => $event->userId,
                'email' => $event->email,
            ],
            referenceType: 'user',
            referenceId: $event->userId,
        );
    }

    private function onMfaSetupCompleted(DomainEventMessage $message): void
    {
        /** @var MfaSetupCompleted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::MFA_SETUP_COMPLETED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Autenticación multifactor configurada',
            bodyTemplate: 'La autenticación multifactor (MFA) ha sido configurada exitosamente en su cuenta.',
            templateVars: [
                'userId' => $event->userId,
            ],
            referenceType: 'user',
            referenceId: $event->userId,
        );
    }

    private function onMfaDisabledByAdmin(DomainEventMessage $message): void
    {
        /** @var MfaDisabledByAdmin $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::MFA_DISABLED_BY_ADMIN,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'MFA deshabilitado por administrador',
            bodyTemplate: 'La autenticación multifactor (MFA) de su cuenta ha sido deshabilitada por un administrador.',
            templateVars: [
                'userId' => $event->userId,
                'disabledBy' => $event->disabledBy,
            ],
            referenceType: 'user',
            referenceId: $event->userId,
        );
    }

    private function onRoleCreated(DomainEventMessage $message): void
    {
        /** @var RoleCreated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::ROLE_CREATED,
            recipients: [],
            subject: 'Rol creado',
            bodyTemplate: 'Se ha creado el rol {{roleName}}.',
            templateVars: [
                'roleId' => $event->roleId,
                'roleName' => $event->roleName,
            ],
            referenceType: 'role',
            referenceId: $event->roleId,
        );
    }

    private function onRoleDeleted(DomainEventMessage $message): void
    {
        /** @var RoleDeleted $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::ROLE_DELETED,
            recipients: [],
            subject: 'Rol eliminado',
            bodyTemplate: 'El rol ha sido eliminado del sistema.',
            templateVars: [
                'roleId' => $event->roleId,
            ],
            referenceType: 'role',
            referenceId: $event->roleId,
        );
    }

    private function onRolePermissionsUpdated(DomainEventMessage $message): void
    {
        /** @var RolePermissionsUpdated $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::ROLE_PERMISSIONS_UPDATED,
            recipients: [],
            subject: 'Permisos de rol actualizados',
            bodyTemplate: 'Los permisos del rol han sido actualizados.',
            templateVars: [
                'roleId' => $event->roleId,
            ],
            referenceType: 'role',
            referenceId: $event->roleId,
        );
    }

    private function onEmployeeRegistered(DomainEventMessage $message): void
    {
        /** @var EmployeeRegistered $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::EMPLOYEE_REGISTERED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Empleado registrado como usuario',
            bodyTemplate: 'El empleado ha sido registrado y vinculado al usuario {{username}}.',
            templateVars: [
                'userId' => $event->userId,
                'employeeId' => $event->employeeId,
                'username' => $event->username,
            ],
            referenceType: 'employee',
            referenceId: $event->employeeId,
        );
    }

    private function onContactLinked(DomainEventMessage $message): void
    {
        /** @var ContactLinked $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::CONTACT_LINKED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Contacto vinculado a usuario',
            bodyTemplate: 'Se ha vinculado un contacto de cliente a su cuenta de usuario.',
            templateVars: [
                'userId' => $event->userId,
                'contactId' => $event->contactId,
                'clientId' => $event->clientId,
            ],
            referenceType: 'contact',
            referenceId: $event->contactId,
        );
    }

    private function onAdminSeeded(DomainEventMessage $message): void
    {
        /** @var AdminSeeded $event */
        $event = $message->restoreEvent();

        $this->dispatcher->dispatchForEvent(
            eventType: EventType::ADMIN_SEEDED,
            recipients: [
                ['type' => RecipientType::USER, 'id' => $event->userId],
            ],
            subject: 'Administrador inicial creado',
            bodyTemplate: 'El usuario administrador inicial ha sido creado exitosamente.',
            templateVars: [
                'userId' => $event->userId,
            ],
            referenceType: 'user',
            referenceId: $event->userId,
        );
    }
}
