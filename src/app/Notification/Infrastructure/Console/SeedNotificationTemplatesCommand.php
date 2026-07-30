<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Console;

use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Repositories\NotificationTemplateRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Siembra las plantillas de notificación para los eventos de solicitudes de stock.
 *
 * Uso:
 *   symfony console orbis:notifications:seed-templates           # crear templates faltantes
 *   symfony console orbis:notifications:seed-templates --dry-run # simular sin persistir
 *
 * Las plantillas ya existentes se omiten (idempotente).
 */
#[AsCommand(
    name: 'orbis:notifications:seed-templates',
    description: 'Siembra las plantillas de notificación para eventos de solicitudes de stock.',
)]
final class SeedNotificationTemplatesCommand extends Command
{
    /**
     * Catálogo de plantillas por EventType y canal.
     *
     * Formato: [eventType, channel, subject, bodyTemplate]
     *
     * @var array<int, array{0: EventType, 1: NotificationChannel, 2: ?string, 3: string}>
     */
    private const TEMPLATES = [
        // ── Ticket Created ─────────────────────────────────────────────────────
        [EventType::TICKET_CREATED, NotificationChannel::IN_APP, 'Ticket creado: {{ticketNumber}}', 'Se ha creado el ticket {{ticketNumber}} para el servicio {{serviceType}}.'],
        [EventType::TICKET_CREATED, NotificationChannel::EMAIL, 'Ticket creado: {{ticketNumber}}', 'Se ha creado el ticket {{ticketNumber}} para el servicio {{serviceType}}. Revise los detalles en la plataforma.'],

        // ── Ticket Assigned ────────────────────────────────────────────────────
        [EventType::TICKET_ASSIGNED, NotificationChannel::IN_APP, 'Ticket asignado', 'Se le ha asignado un ticket. Fecha programada: {{scheduledAt}}.'],
        [EventType::TICKET_ASSIGNED, NotificationChannel::EMAIL, 'Ticket asignado', 'Se le ha asignado un ticket programado para {{scheduledAt}}. Ingrese a la plataforma para más detalles.'],

        // ── Ticket Reassigned ──────────────────────────────────────────────────
        [EventType::TICKET_REASSIGNED, NotificationChannel::IN_APP, 'Ticket reasignado', 'Se le ha reasignado un ticket previamente asignado a otro técnico.'],
        [EventType::TICKET_REASSIGNED, NotificationChannel::EMAIL, 'Ticket reasignado', 'Se le ha reasignado un ticket. Verifique los detalles en la plataforma.'],

        // ── Ticket Rescheduled ─────────────────────────────────────────────────
        [EventType::TICKET_RESCHEDULED, NotificationChannel::IN_APP, 'Ticket reprogramado', 'El ticket ha sido reprogramado de {{previousDate}} a {{newDate}}. Motivo: {{reason}}.'],
        [EventType::TICKET_RESCHEDULED, NotificationChannel::EMAIL, 'Ticket reprogramado', 'El ticket ha sido reprogramado de {{previousDate}} a {{newDate}}. Motivo: {{reason}}.'],

        // ── Ticket Cancelled ───────────────────────────────────────────────────
        [EventType::TICKET_CANCELLED, NotificationChannel::IN_APP, 'Ticket cancelado', 'El ticket ha sido cancelado. Motivo: {{reason}}.'],
        [EventType::TICKET_CANCELLED, NotificationChannel::EMAIL, 'Ticket cancelado', 'El ticket ha sido cancelado. Motivo: {{reason}}.'],

        // ── Ticket Closed ──────────────────────────────────────────────────────
        [EventType::TICKET_CLOSED, NotificationChannel::IN_APP, 'Ticket cerrado', 'El ticket ha sido cerrado. Código CAT: {{catCode}}.'],
        [EventType::TICKET_CLOSED, NotificationChannel::EMAIL, 'Ticket cerrado', 'El ticket ha sido cerrado. Código CAT: {{catCode}}.'],

        // ── Expense Item Rejected ──────────────────────────────────────────────
        [EventType::EXPENSE_ITEM_REJECTED, NotificationChannel::IN_APP, 'Gasto rechazado', 'Un item de gasto ha sido rechazado. Motivo: {{rejectionReason}}.'],
        [EventType::EXPENSE_ITEM_REJECTED, NotificationChannel::EMAIL, 'Gasto rechazado', 'Un item de su liquidación ha sido rechazado. Motivo: {{rejectionReason}}. Revise y corrija.'],

        // ── Expense Approved ───────────────────────────────────────────────────
        [EventType::EXPENSE_APPROVED, NotificationChannel::IN_APP, 'Liquidación aprobada', 'La liquidación ha sido aprobada por un monto de {{totalAmount}}.'],
        [EventType::EXPENSE_APPROVED, NotificationChannel::EMAIL, 'Liquidación aprobada', 'Su liquidación por {{totalAmount}} ha sido aprobada.'],

        // ── Expense Submitted ──────────────────────────────────────────────────
        [EventType::EXPENSE_SUBMITTED, NotificationChannel::IN_APP, 'Liquidación enviada', 'Su liquidación ha sido enviada para revisión. Total: {{totalAmount}} ({{totalItems}} items).'],
        [EventType::EXPENSE_SUBMITTED, NotificationChannel::EMAIL, 'Liquidación enviada', 'Su liquidación por {{totalAmount}} ({{totalItems}} items) ha sido enviada para revisión.'],

        // ── Expense Resubmitted ────────────────────────────────────────────────
        [EventType::EXPENSE_RESUBMITTED, NotificationChannel::IN_APP, 'Liquidación reenviada', 'Su liquidación ha sido reenviada para revisión. Total: {{totalAmount}} ({{totalItems}} items).'],
        [EventType::EXPENSE_RESUBMITTED, NotificationChannel::EMAIL, 'Liquidación reenviada', 'Su liquidación por {{totalAmount}} ha sido reenviada para revisión.'],

        // ── Cash Advance Created ──────────────────────────────────────────────
        [EventType::CASH_ADVANCE_CREATED, NotificationChannel::IN_APP, 'Adelanto creado', 'Se ha creado un adelanto de {{amount}} a su nombre. N° {{advanceNumber}}.'],
        [EventType::CASH_ADVANCE_CREATED, NotificationChannel::EMAIL, 'Adelanto creado', 'Se ha creado un adelanto de {{amount}} a su nombre. N° {{advanceNumber}}.'],

        // ── Cash Advance Updated ──────────────────────────────────────────────
        [EventType::CASH_ADVANCE_UPDATED, NotificationChannel::IN_APP, 'Adelanto actualizado', 'El adelanto #{{cashAdvanceId}} ha sido actualizado de {{previousAmount}} a {{newAmount}}.'],

        // ── Cash Advance Pending Payment ──────────────────────────────────────
        [EventType::CASH_ADVANCE_PENDING_PAYMENT, NotificationChannel::IN_APP, 'Adelanto pendiente de pago', 'Tiene un adelanto de {{amount}} pendiente de pago desde hace {{daysSinceGiven}} días.'],
        [EventType::CASH_ADVANCE_PENDING_PAYMENT, NotificationChannel::EMAIL, 'Adelanto pendiente de pago', 'Tiene un adelanto de {{amount}} pendiente de pago. Regularice a la brevedad.'],

        // ── Cash Advance Pending Review ───────────────────────────────────────
        [EventType::CASH_ADVANCE_PENDING_REVIEW, NotificationChannel::IN_APP, 'Adelanto pendiente de revisión', 'El adelanto #{{cashAdvanceId}} de {{amount}} del empleado {{employeeId}} está pendiente de revisión.'],
        [EventType::CASH_ADVANCE_PENDING_REVIEW, NotificationChannel::EMAIL, 'Adelanto pendiente de revisión', 'El adelanto #{{cashAdvanceId}} de {{amount}} requiere revisión financiera.'],

        // ── Cash Advance Settled ──────────────────────────────────────────────
        [EventType::CASH_ADVANCE_SETTLED, NotificationChannel::IN_APP, 'Adelanto liquidado', 'El adelanto de {{amount}} ha sido liquidado. Dirección: {{direction}}.'],
        [EventType::CASH_ADVANCE_SETTLED, NotificationChannel::EMAIL, 'Adelanto liquidado', 'El adelanto de {{amount}} ha sido liquidado con dirección {{direction}}.'],

        // ── User Registered ───────────────────────────────────────────────────
        [EventType::USER_REGISTERED, NotificationChannel::IN_APP, 'Usuario registrado', 'El usuario {{username}} ha sido registrado exitosamente.'],
        [EventType::USER_REGISTERED, NotificationChannel::EMAIL, 'Usuario registrado', 'Bienvenido {{username}}. Su cuenta ha sido registrada exitosamente en Orbis.'],

        // ── User Account Activated ────────────────────────────────────────────
        [EventType::USER_ACCOUNT_ACTIVATED, NotificationChannel::IN_APP, 'Cuenta activada', 'Su cuenta de usuario {{username}} ha sido activada.'],
        [EventType::USER_ACCOUNT_ACTIVATED, NotificationChannel::EMAIL, 'Cuenta activada', 'Su cuenta {{username}} ha sido activada. Ya puede iniciar sesión en Orbis.'],

        // ── User Password Reset ───────────────────────────────────────────────
        [EventType::USER_PASSWORD_RESET, NotificationChannel::IN_APP, 'Contraseña restablecida', 'Su contraseña ha sido restablecida exitosamente.'],
        [EventType::USER_PASSWORD_RESET, NotificationChannel::EMAIL, 'Contraseña restablecida', 'Su contraseña ha sido restablecida exitosamente. Si no realizó esta acción, contacte a soporte.'],

        // ── Stock Below Threshold ─────────────────────────────────────────────
        [EventType::STOCK_BELOW_THRESHOLD, NotificationChannel::IN_APP, 'Stock por debajo del umbral', 'El producto ha alcanzado un nivel de stock por debajo del umbral mínimo en la ubicación.'],
        [EventType::STOCK_BELOW_THRESHOLD, NotificationChannel::EMAIL, 'Alerta de stock mínimo', 'El producto {{productId}} tiene stock por debajo del umbral mínimo en la ubicación {{locationId}}. Requiere reposición.'],

        // ── Emergency Part Used ───────────────────────────────────────────────
        [EventType::EMERGENCY_PART_USED, NotificationChannel::IN_APP, 'Repuesto de emergencia usado', 'Se ha utilizado un repuesto de emergencia.{{notes}}'],
        [EventType::EMERGENCY_PART_USED, NotificationChannel::EMAIL, 'Repuesto de emergencia usado', 'Se ha utilizado un repuesto de emergencia.{{notes}} Revise el inventario para reposición.'],

        // ── Part Repaired ─────────────────────────────────────────────────────
        [EventType::PART_REPAIRED, NotificationChannel::IN_APP, 'Repuesto reparado', 'El repuesto ha sido reparado.'],

        // ── Employee Special Period Starting ───────────────────────────────────
        [EventType::EMPLOYEE_SPECIAL_PERIOD_STARTING, NotificationChannel::IN_APP, 'Periodo especial programado', 'Se ha programado un período especial de tipo {{type}} para el empleado.'],
        [EventType::EMPLOYEE_SPECIAL_PERIOD_STARTING, NotificationChannel::EMAIL, 'Periodo especial programado', 'Se ha programado un período especial ({{type}}) para el empleado {{employeeId}}.'],

        // ── SLA About to Expire ───────────────────────────────────────────────
        [EventType::SLA_ABOUT_TO_EXPIRE, NotificationChannel::IN_APP, 'SLA próximo a vencer: {{ticketNumber}}', 'El ticket {{ticketNumber}} tiene un SLA próximo a vencer (deadline: {{slaDeadline}}).'],
        [EventType::SLA_ABOUT_TO_EXPIRE, NotificationChannel::EMAIL, 'SLA próximo a vencer: {{ticketNumber}}', 'El ticket {{ticketNumber}} tiene SLA próximo a vencer el {{slaDeadline}}. Se requiere atención prioritaria.'],

        // ── SLA Breached ─────────────────────────────────────────────────────
        [EventType::SLA_BREACHED, NotificationChannel::IN_APP, 'SLA vencido: {{ticketNumber}}', 'El SLA del ticket {{ticketNumber}} ha vencido (deadline era: {{slaDeadline}}). Se requiere atención inmediata.'],
        [EventType::SLA_BREACHED, NotificationChannel::EMAIL, 'SLA vencido: {{ticketNumber}}', 'El SLA del ticket {{ticketNumber}} ha vencido. Deadline: {{slaDeadline}}. Se requiere atención inmediata.'],

        // ── Warranty Expiring Soon ────────────────────────────────────────────
        [EventType::WARRANTY_EXPIRING_SOON, NotificationChannel::IN_APP, 'Garantía próxima a vencer', 'La garantía del producto está próxima a vencer. Revise los detalles.'],
        [EventType::WARRANTY_EXPIRING_SOON, NotificationChannel::EMAIL, 'Garantía próxima a vencer', 'La garantía de un producto está próxima a vencer. Coordine renovación si es necesario.'],

        // ── RequestSubmitted ──────────────────────────────────────────────────
        [EventType::REQUEST_SUBMITTED, NotificationChannel::IN_APP, 'Nueva solicitud: {{reference}}', 'Se ha creado una solicitud de repuesto. Referencia: {{reference}}.'],
        [EventType::REQUEST_SUBMITTED, NotificationChannel::EMAIL, 'Nueva solicitud: {{reference}}', 'Se ha creado una solicitud de repuesto con referencia {{reference}}. Producto: {{productId}}, Cantidad: {{quantity}}.'],

        // ── RequestAwaitingTechnicianAcceptance ────────────────────────────────
        [EventType::REQUEST_AWAITING_TECHNICIAN_ACCEPTANCE, NotificationChannel::IN_APP, 'Solicitud aprobada: {{reference}}', 'Tu solicitud {{reference}} ha sido aprobada. Debes aceptar el traslado.'],
        [EventType::REQUEST_AWAITING_TECHNICIAN_ACCEPTANCE, NotificationChannel::EMAIL, 'Solicitud aprobada: {{reference}}', 'Tu solicitud {{reference}} ha sido aprobada. Debes aceptar el traslado para completar el proceso.'],

        // ── RequestRejected ────────────────────────────────────────────────────
        [EventType::REQUEST_REJECTED, NotificationChannel::IN_APP, 'Solicitud rechazada: {{reference}}', 'Tu solicitud {{reference}} ha sido rechazada.'],
        [EventType::REQUEST_REJECTED, NotificationChannel::EMAIL, 'Solicitud rechazada: {{reference}}', 'Tu solicitud {{reference}} ha sido rechazada. Contacta a tu supervisor para más detalles.'],

        // ── RequestCancelled ───────────────────────────────────────────────────
        [EventType::REQUEST_CANCELLED, NotificationChannel::IN_APP, 'Solicitud cancelada: {{reference}}', 'La solicitud {{reference}} ha sido cancelada.'],
        [EventType::REQUEST_CANCELLED, NotificationChannel::EMAIL, 'Solicitud cancelada: {{reference}}', 'La solicitud {{reference}} ha sido cancelada.'],

        // ── RequestFulfilled ───────────────────────────────────────────────────
        [EventType::REQUEST_FULFILLED, NotificationChannel::IN_APP, 'Solicitud ejecutada: {{reference}}', 'Tu solicitud {{reference}} ha sido ejecutada. El stock ha sido transferido.'],
        [EventType::REQUEST_FULFILLED, NotificationChannel::EMAIL, 'Solicitud ejecutada: {{reference}}', 'Tu solicitud {{reference}} ha sido ejecutada. El stock ha sido transferido a tu ubicación.'],

        // ── Ticket Arrived At Site ────────────────────────────────────────────
        [EventType::TICKET_ARRIVED_AT_SITE, NotificationChannel::IN_APP, 'Llegada al sitio: {{ticketId}}', 'El técnico ha llegado al sitio del ticket.'],
        [EventType::TICKET_ARRIVED_AT_SITE, NotificationChannel::EMAIL, 'Llegada al sitio', 'El técnico ha llegado al sitio del ticket {{ticketId}} a las {{arrivedAt}}.'],

        // ── Ticket Assistance Started ──────────────────────────────────────────
        [EventType::TICKET_ASSISTANCE_STARTED, NotificationChannel::IN_APP, 'Asistencia iniciada', 'La asistencia técnica ha iniciado en el ticket.'],
        [EventType::TICKET_ASSISTANCE_STARTED, NotificationChannel::EMAIL, 'Asistencia iniciada', 'La asistencia técnica ha iniciado en el ticket {{ticketId}} a las {{startedAt}}.'],

        // ── Ticket Assistance Completed ────────────────────────────────────────
        [EventType::TICKET_ASSISTANCE_COMPLETED, NotificationChannel::IN_APP, 'Asistencia completada', 'La asistencia técnica ha sido completada.'],
        [EventType::TICKET_ASSISTANCE_COMPLETED, NotificationChannel::EMAIL, 'Asistencia completada', 'La asistencia técnica ha sido completada en el ticket {{ticketId}} a las {{completedAt}}.'],

        // ── Ticket Finding Registered ──────────────────────────────────────────
        [EventType::TICKET_FINDING_REGISTERED, NotificationChannel::IN_APP, 'Hallazgo registrado', 'Se ha registrado un hallazgo. Tipo: {{problemTypeFound}}.'],

        // ── Ticket Journey Started ─────────────────────────────────────────────
        [EventType::TICKET_JOURNEY_STARTED, NotificationChannel::IN_APP, 'Viaje iniciado', 'Se ha iniciado un viaje de tipo {{journeyType}} para el ticket.'],

        // ── Ticket Paused ──────────────────────────────────────────────────────
        [EventType::TICKET_PAUSED, NotificationChannel::IN_APP, 'Ticket pausado', 'El ticket ha sido pausado. Motivo: {{reason}}.'],
        [EventType::TICKET_PAUSED, NotificationChannel::EMAIL, 'Ticket pausado', 'El ticket {{ticketId}} ha sido pausado por {{pausedBy}}. Motivo: {{reason}}.'],

        // ── Ticket Waiting Started ─────────────────────────────────────────────
        [EventType::TICKET_WAITING_STARTED, NotificationChannel::IN_APP, 'Espera iniciada', 'Se ha iniciado un período de espera en el ticket.'],

        // ── Ticket Resumed ─────────────────────────────────────────────────────
        [EventType::TICKET_RESUMED, NotificationChannel::IN_APP, 'Ticket reanudado', 'El ticket ha sido reanudado.'],
        [EventType::TICKET_RESUMED, NotificationChannel::EMAIL, 'Ticket reanudado', 'El ticket {{ticketId}} ha sido reanudado a las {{resumedAt}}.'],

        // ── Ticket Reopen Requested ────────────────────────────────────────────
        [EventType::TICKET_REOPEN_REQUESTED, NotificationChannel::IN_APP, 'Solicitud de reapertura', 'Se ha solicitado la reapertura del ticket. Motivo: {{reason}}.'],
        [EventType::TICKET_REOPEN_REQUESTED, NotificationChannel::EMAIL, 'Solicitud de reapertura', 'Se ha solicitado la reapertura del ticket {{ticketId}}. Motivo: {{reason}}.'],

        // ── Ticket Reopened ────────────────────────────────────────────────────
        [EventType::TICKET_REOPENED, NotificationChannel::IN_APP, 'Ticket reabierto', 'El ticket ha sido reabierto. Motivo: {{reason}}.'],

        // ── Request Approved ───────────────────────────────────────────────────
        [EventType::REQUEST_APPROVED, NotificationChannel::IN_APP, 'Solicitud aprobada', 'La solicitud de repuesto ha sido aprobada.'],
        [EventType::REQUEST_APPROVED, NotificationChannel::EMAIL, 'Solicitud aprobada', 'La solicitud {{requestId}} ha sido aprobada.'],

        // ── Stock Moved ────────────────────────────────────────────────────────
        [EventType::STOCK_MOVED, NotificationChannel::IN_APP, 'Movimiento de stock completado', 'Se ha completado un movimiento de stock.'],
        [EventType::STOCK_MOVED, NotificationChannel::EMAIL, 'Movimiento de stock completado', 'Se ha completado el movimiento de stock {{movementId}}.'],

        // ── Serialized Stock Created ───────────────────────────────────────────
        [EventType::SERIALIZED_STOCK_CREATED, NotificationChannel::IN_APP, 'Stock serializado registrado', 'Se ha registrado un nuevo ítem de stock serializado.'],

        // ── Entry Confirmed ────────────────────────────────────────────────────
        [EventType::ENTRY_CONFIRMED, NotificationChannel::IN_APP, 'Entrada confirmada', 'Se ha confirmado una entrada de inventario.'],
        [EventType::ENTRY_CONFIRMED, NotificationChannel::EMAIL, 'Entrada confirmada', 'Se ha confirmado la entrada de inventario {{entryId}}.'],

        // ── Product Created ────────────────────────────────────────────────────
        [EventType::PRODUCT_CREATED, NotificationChannel::IN_APP, 'Producto creado', 'Se ha creado un nuevo producto. Código: {{productCode}}.'],

        // ── Employee Created ───────────────────────────────────────────────────
        [EventType::EMPLOYEE_CREATED, NotificationChannel::IN_APP, 'Empleado creado', 'Se ha creado el empleado {{firstName}} {{lastName}}.'],
        [EventType::EMPLOYEE_CREATED, NotificationChannel::EMAIL, 'Empleado creado', 'Se ha creado el empleado {{firstName}} {{lastName}} ({{email}}).'],

        // ── Employee Deactivated ───────────────────────────────────────────────
        [EventType::EMPLOYEE_DEACTIVATED, NotificationChannel::IN_APP, 'Empleado desactivado', 'El empleado ha sido desactivado.'],

        // ── Vacation Requested ─────────────────────────────────────────────────
        [EventType::VACATION_REQUESTED, NotificationChannel::IN_APP, 'Vacaciones solicitadas', 'Se ha solicitado vacaciones del {{startDate}} al {{endDate}}.'],
        [EventType::VACATION_REQUESTED, NotificationChannel::EMAIL, 'Vacaciones solicitadas', 'Se ha solicitado vacaciones del {{startDate}} al {{endDate}}. Revise la solicitud.'],

        // ── Vacation Approved ──────────────────────────────────────────────────
        [EventType::VACATION_APPROVED, NotificationChannel::IN_APP, 'Vacaciones aprobadas', 'Las vacaciones del {{startDate}} al {{endDate}} han sido aprobadas.'],
        [EventType::VACATION_APPROVED, NotificationChannel::EMAIL, 'Vacaciones aprobadas', 'Las vacaciones del {{startDate}} al {{endDate}} han sido aprobadas. Disfrute su descanso.'],

        // ── Client Created ─────────────────────────────────────────────────────
        [EventType::CLIENT_CREATED, NotificationChannel::IN_APP, 'Cliente creado', 'Se ha creado el cliente {{clientName}}.'],

        // ── Contact Added ──────────────────────────────────────────────────────
        [EventType::CONTACT_ADDED, NotificationChannel::IN_APP, 'Contacto agregado', 'Se ha agregado un nuevo contacto al cliente.'],

        // ── Client Deactivated ───────────────────────────────────────────────
        [EventType::CLIENT_DEACTIVATED, NotificationChannel::IN_APP, 'Cliente desactivado', 'El cliente ha sido desactivado.{{reason}}'],

        // ── Client Reactivated ───────────────────────────────────────────────
        [EventType::CLIENT_REACTIVATED, NotificationChannel::IN_APP, 'Cliente reactivado', 'El cliente ha sido reactivado.'],

        // ── Client Deleted ────────────────────────────────────────────────────
        [EventType::CLIENT_DELETED, NotificationChannel::IN_APP, 'Cliente eliminado', 'El cliente ha sido eliminado.{{reason}}'],
    ];

    public function __construct(
        private readonly NotificationTemplateRepositoryInterface $templateRepository,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simula la siembra sin persistir nada.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');

        $io->title('Siembra de plantillas de notificación — Orbis');

        if ($isDryRun) {
            $io->warning('Modo DRY-RUN: ningún cambio será persistido.');
        }

        $created = 0;
        $skipped = 0;

        foreach (self::TEMPLATES as [$eventType, $channel, $subject, $bodyTemplate]) {
            $existing = $this->templateRepository->findByEventTypeAndChannel($eventType, $channel);

            if ($existing !== null) {
                $io->writeln(sprintf(
                    '  <comment>↷ Existente</comment> %s / %s',
                    $eventType->value,
                    $channel->value,
                ));
                $skipped++;

                continue;
            }

            if (!$isDryRun) {
                $template = \App\Notification\Domain\Entities\NotificationTemplate::create(
                    id: new NotificationTemplateIdValueObject($this->idGenerator->generate()),
                    eventType: $eventType,
                    channel: $channel,
                    subject: $subject,
                    bodyTemplate: $bodyTemplate,
                    isActive: true,
                );

                $this->templateRepository->save($template);
            }

            $io->writeln(sprintf(
                '  <info>✓ Creado</info>    %s / %s',
                $eventType->value,
                $channel->value,
            ));
            $created++;
        }

        $io->newLine();
        $io->success(sprintf(
            '%d plantillas creadas, %d ya existían.%s',
            $created,
            $skipped,
            $isDryRun ? ' (dry-run, nada fue persistido)' : '',
        ));

        return Command::SUCCESS;
    }
}
