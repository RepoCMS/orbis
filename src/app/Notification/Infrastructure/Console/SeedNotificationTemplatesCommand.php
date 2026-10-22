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
        [EventType::CASH_ADVANCE_UPDATED, NotificationChannel::EMAIL, 'Adelanto actualizado', 'El adelanto #{{cashAdvanceId}} ha sido actualizado de {{previousAmount}} a {{newAmount}}.'],

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
        [EventType::PART_REPAIRED, NotificationChannel::EMAIL, 'Repuesto reparado', 'El repuesto con ID {{serializedStockId}} ha sido reparado y está disponible.'],

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
        [EventType::REQUEST_APPROVED, NotificationChannel::EMAIL, 'Solicitud aprobada', 'La solicitud {{requestId}} ha sido aprobada. El técnico responsable ha sido notificado para proceder con el traslado.'],

        // ── Stock Moved ────────────────────────────────────────────────────────
        [EventType::STOCK_MOVED, NotificationChannel::IN_APP, 'Movimiento de stock completado', 'Se ha completado un movimiento de stock.'],
        [EventType::STOCK_MOVED, NotificationChannel::EMAIL, 'Movimiento de stock completado', 'Se ha completado el movimiento de stock {{movementId}}.'],

        // ── Serialized Stock Created ───────────────────────────────────────────
        [EventType::SERIALIZED_STOCK_CREATED, NotificationChannel::IN_APP, 'Stock serializado registrado', 'Se ha registrado un nuevo ítem de stock serializado.'],
        [EventType::SERIALIZED_STOCK_CREATED, NotificationChannel::EMAIL, 'Stock serializado registrado', 'Se ha registrado un nuevo ítem de stock serializado con ID {{serializedStockId}}.'],

        // ── Entry Confirmed ────────────────────────────────────────────────────
        [EventType::ENTRY_CONFIRMED, NotificationChannel::IN_APP, 'Entrada confirmada', 'Se ha confirmado una entrada de inventario.'],
        [EventType::ENTRY_CONFIRMED, NotificationChannel::EMAIL, 'Entrada confirmada', 'Se ha confirmado la entrada de inventario {{entryId}}.'],

        // ── Product Created ────────────────────────────────────────────────────
        [EventType::PRODUCT_CREATED, NotificationChannel::IN_APP, 'Producto creado', 'Se ha creado un nuevo producto. Código: {{productCode}}.'],
        [EventType::PRODUCT_CREATED, NotificationChannel::EMAIL, 'Producto creado', 'Se ha creado un nuevo producto en el catálogo. Código: {{productCode}}, ID: {{productId}}.'],

        // ── Employee Created ───────────────────────────────────────────────────
        [EventType::EMPLOYEE_CREATED, NotificationChannel::IN_APP, 'Empleado creado', 'Se ha creado el empleado {{firstName}} {{lastName}}.'],
        [EventType::EMPLOYEE_CREATED, NotificationChannel::EMAIL, 'Empleado creado', 'Se ha creado el empleado {{firstName}} {{lastName}} ({{email}}).'],

        // ── Employee Deactivated ───────────────────────────────────────────────
        [EventType::EMPLOYEE_DEACTIVATED, NotificationChannel::IN_APP, 'Empleado desactivado', 'El empleado ha sido desactivado.'],
        [EventType::EMPLOYEE_DEACTIVATED, NotificationChannel::EMAIL, 'Empleado desactivado', 'El empleado ha sido desactivado. Su acceso al sistema ha sido removido.'],

        // ── Vacation Requested ─────────────────────────────────────────────────
        [EventType::VACATION_REQUESTED, NotificationChannel::IN_APP, 'Vacaciones solicitadas', 'Se ha solicitado vacaciones del {{startDate}} al {{endDate}}.'],
        [EventType::VACATION_REQUESTED, NotificationChannel::EMAIL, 'Vacaciones solicitadas', 'Se ha solicitado vacaciones del {{startDate}} al {{endDate}}. Revise la solicitud.'],

        // ── Vacation Approved ──────────────────────────────────────────────────
        [EventType::VACATION_APPROVED, NotificationChannel::IN_APP, 'Vacaciones aprobadas', 'Las vacaciones del {{startDate}} al {{endDate}} han sido aprobadas.'],
        [EventType::VACATION_APPROVED, NotificationChannel::EMAIL, 'Vacaciones aprobadas', 'Las vacaciones del {{startDate}} al {{endDate}} han sido aprobadas. Disfrute su descanso.'],

        // ── Client Created ─────────────────────────────────────────────────────
        [EventType::CLIENT_CREATED, NotificationChannel::IN_APP, 'Cliente creado', 'Se ha creado el cliente {{clientName}}.'],
        [EventType::CLIENT_CREATED, NotificationChannel::EMAIL, 'Cliente creado', 'Se ha creado un nuevo cliente: {{clientName}} (ID: {{clientId}}).'],

        // ── Contact Added ──────────────────────────────────────────────────────
        [EventType::CONTACT_ADDED, NotificationChannel::IN_APP, 'Contacto agregado', 'Se ha agregado un nuevo contacto al cliente.'],
        [EventType::CONTACT_ADDED, NotificationChannel::EMAIL, 'Contacto agregado', 'Se ha agregado un nuevo contacto al cliente {{clientName}}.'],

        // ── Contact Updated ───────────────────────────────────────────────────
        [EventType::CONTACT_UPDATED, NotificationChannel::IN_APP, 'Contacto actualizado', 'El contacto ha sido actualizado.'],
        [EventType::CONTACT_UPDATED, NotificationChannel::EMAIL, 'Contacto actualizado', 'El contacto {{contactName}} del cliente {{clientName}} ha sido actualizado.'],

        // ── Contact Deactivated ───────────────────────────────────────────────
        [EventType::CONTACT_DEACTIVATED, NotificationChannel::IN_APP, 'Contacto desactivado', 'El contacto ha sido desactivado.'],
        [EventType::CONTACT_DEACTIVATED, NotificationChannel::EMAIL, 'Contacto desactivado', 'El contacto {{contactName}} del cliente {{clientName}} ha sido desactivado.'],

        // ── Contact Reactivated ───────────────────────────────────────────────
        [EventType::CONTACT_REACTIVATED, NotificationChannel::IN_APP, 'Contacto reactivado', 'El contacto ha sido reactivado.'],
        [EventType::CONTACT_REACTIVATED, NotificationChannel::EMAIL, 'Contacto reactivado', 'El contacto {{contactName}} del cliente {{clientName}} ha sido reactivado.'],

        // ── Contact User Unlinked ─────────────────────────────────────────────
        [EventType::CONTACT_USER_UNLINKED, NotificationChannel::IN_APP, 'Contacto desvinculado', 'El contacto ha sido desvinculado del usuario.'],
        [EventType::CONTACT_USER_UNLINKED, NotificationChannel::EMAIL, 'Contacto desvinculado', 'El contacto {{contactName}} ha sido desvinculado del usuario.'],

        // ── Contact Access Provisioned ────────────────────────────────────────
        [EventType::CONTACT_ACCESS_PROVISIONED, NotificationChannel::IN_APP, 'Acceso provisto', 'Se ha provisto acceso al contacto.'],
        [EventType::CONTACT_ACCESS_PROVISIONED, NotificationChannel::EMAIL, 'Acceso provisto', 'Se ha provisto acceso al portal para el contacto {{contactName}}.'],

        // ── Client Deactivated ───────────────────────────────────────────────
        [EventType::CLIENT_DEACTIVATED, NotificationChannel::IN_APP, 'Cliente desactivado', 'El cliente ha sido desactivado.{{reason}}'],
        [EventType::CLIENT_DEACTIVATED, NotificationChannel::EMAIL, 'Cliente desactivado', 'El cliente {{clientName}} ha sido desactivado.{{reason}}'],

        // ── Client Reactivated ───────────────────────────────────────────────
        [EventType::CLIENT_REACTIVATED, NotificationChannel::IN_APP, 'Cliente reactivado', 'El cliente ha sido reactivado.'],
        [EventType::CLIENT_REACTIVATED, NotificationChannel::EMAIL, 'Cliente reactivado', 'El cliente {{clientName}} ha sido reactivado.'],

        // ── Client Deleted ────────────────────────────────────────────────────
        [EventType::CLIENT_DELETED, NotificationChannel::IN_APP, 'Cliente eliminado', 'El cliente ha sido eliminado.{{reason}}'],
        [EventType::CLIENT_DELETED, NotificationChannel::EMAIL, 'Cliente eliminado', 'El cliente {{clientName}} ha sido eliminado.{{reason}}'],

        // ── Terminal Registered ────────────────────────────────────────────────────
        [EventType::TERMINAL_REGISTERED, NotificationChannel::IN_APP, 'Terminal registrada', 'Se ha registrado una nueva terminal.'],
        [EventType::TERMINAL_REGISTERED, NotificationChannel::EMAIL, 'Terminal registrada', 'Se ha registrado una nueva terminal: {{terminalName}} ({{serialNumber}}).'],

        // ── Terminal Updated ────────────────────────────────────────────────────
        [EventType::TERMINAL_UPDATED, NotificationChannel::IN_APP, 'Terminal actualizada', 'La terminal ha sido actualizada.'],
        [EventType::TERMINAL_UPDATED, NotificationChannel::EMAIL, 'Terminal actualizada', 'La terminal {{terminalName}} ({{serialNumber}}) ha sido actualizada.'],

        // ── Terminal Deactivated ────────────────────────────────────────────────
        [EventType::TERMINAL_DEACTIVATED, NotificationChannel::IN_APP, 'Terminal desactivada', 'La terminal ha sido desactivada.'],
        [EventType::TERMINAL_DEACTIVATED, NotificationChannel::EMAIL, 'Terminal desactivada', 'La terminal {{terminalName}} ({{serialNumber}}) ha sido desactivada.'],

        // ── Terminal Reactivated ────────────────────────────────────────────────
        [EventType::TERMINAL_REACTIVATED, NotificationChannel::IN_APP, 'Terminal reactivada', 'La terminal ha sido reactivada.'],
        [EventType::TERMINAL_REACTIVATED, NotificationChannel::EMAIL, 'Terminal reactivada', 'La terminal {{terminalName}} ({{serialNumber}}) ha sido reactivada.'],

        // ── Terminal Assignment Registered ──────────────────────────────────────
        [EventType::TERMINAL_ASSIGNMENT_REGISTERED, NotificationChannel::IN_APP, 'Asignación de terminal registrada', 'Se ha registrado una asignación de terminal.'],
        [EventType::TERMINAL_ASSIGNMENT_REGISTERED, NotificationChannel::EMAIL, 'Asignación de terminal registrada', 'Se ha registrado una asignación de terminal para {{terminalName}}.'],

        // ── Terminal Assignment Updated ─────────────────────────────────────────
        [EventType::TERMINAL_ASSIGNMENT_UPDATED, NotificationChannel::IN_APP, 'Asignación de terminal actualizada', 'La asignación de terminal ha sido actualizada.'],
        [EventType::TERMINAL_ASSIGNMENT_UPDATED, NotificationChannel::EMAIL, 'Asignación de terminal actualizada', 'La asignación de terminal para {{terminalName}} ha sido actualizada.'],

        // ── Terminal Assignment Deactivated ─────────────────────────────────────
        [EventType::TERMINAL_ASSIGNMENT_DEACTIVATED, NotificationChannel::IN_APP, 'Asignación de terminal desactivada', 'La asignación de terminal ha sido desactivada.'],
        [EventType::TERMINAL_ASSIGNMENT_DEACTIVATED, NotificationChannel::EMAIL, 'Asignación de terminal desactivada', 'La asignación de terminal para {{terminalName}} ha sido desactivada.'],

        // ── Terminal Assignment Reactivated ─────────────────────────────────────
        [EventType::TERMINAL_ASSIGNMENT_REACTIVATED, NotificationChannel::IN_APP, 'Asignación de terminal reactivada', 'La asignación de terminal ha sido reactivada.'],
        [EventType::TERMINAL_ASSIGNMENT_REACTIVATED, NotificationChannel::EMAIL, 'Asignación de terminal reactivada', 'La asignación de terminal para {{terminalName}} ha sido reactivada.'],

        // ── Contract Created ───────────────────────────────────────────────────
        [EventType::CONTRACT_CREATED, NotificationChannel::IN_APP, 'Contrato creado', 'Se ha creado un nuevo contrato.'],
        [EventType::CONTRACT_CREATED, NotificationChannel::EMAIL, 'Contrato creado', 'Se ha creado un nuevo contrato: {{contractNumber}} para el cliente {{clientName}}.'],

        // ── Contract Updated ────────────────────────────────────────────────────
        [EventType::CONTRACT_UPDATED, NotificationChannel::IN_APP, 'Contrato actualizado', 'El contrato ha sido actualizado.'],
        [EventType::CONTRACT_UPDATED, NotificationChannel::EMAIL, 'Contrato actualizado', 'El contrato {{contractNumber}} ha sido actualizado.'],

        // ── Contract Cancelled ──────────────────────────────────────────────────
        [EventType::CONTRACT_CANCELLED, NotificationChannel::IN_APP, 'Contrato cancelado', 'El contrato ha sido cancelado.'],
        [EventType::CONTRACT_CANCELLED, NotificationChannel::EMAIL, 'Contrato cancelado', 'El contrato {{contractNumber}} ha sido cancelado. Motivo: {{reason}}.'],

        // ── Warranty Registered ─────────────────────────────────────────────────
        [EventType::WARRANTY_REGISTERED, NotificationChannel::IN_APP, 'Garantía registrada', 'Se ha registrado una nueva garantía.'],
        [EventType::WARRANTY_REGISTERED, NotificationChannel::EMAIL, 'Garantía registrada', 'Se ha registrado una garantía para {{productName}} (ID: {{warrantyId}}).'],

        // ── Warranty Updated ────────────────────────────────────────────────────
        [EventType::WARRANTY_UPDATED, NotificationChannel::IN_APP, 'Garantía actualizada', 'La garantía ha sido actualizada.'],
        [EventType::WARRANTY_UPDATED, NotificationChannel::EMAIL, 'Garantía actualizada', 'La garantía {{warrantyId}} ha sido actualizada.'],

        // ── Warranty Expired ────────────────────────────────────────────────────
        [EventType::WARRANTY_EXPIRED, NotificationChannel::IN_APP, 'Garantía vencida', 'La garantía ha vencido.'],
        [EventType::WARRANTY_EXPIRED, NotificationChannel::EMAIL, 'Garantía vencida', 'La garantía {{warrantyId}} para {{productName}} ha vencido.'],

        // ── SLA Policy Created ──────────────────────────────────────────────────
        [EventType::SLA_POLICY_CREATED, NotificationChannel::IN_APP, 'Política de SLA creada', 'Se ha creado una nueva política de SLA.'],
        [EventType::SLA_POLICY_CREATED, NotificationChannel::EMAIL, 'Política de SLA creada', 'Se ha creado una nueva política de SLA: {{policyName}}.'],

        // ── Client Sale Order Completed ─────────────────────────────────────────
        [EventType::CLIENT_SALE_ORDER_COMPLETED, NotificationChannel::IN_APP, 'Orden de venta completada', 'La orden de venta ha sido completada.'],
        [EventType::CLIENT_SALE_ORDER_COMPLETED, NotificationChannel::EMAIL, 'Orden de venta completada', 'La orden de venta {{orderNumber}} ha sido completada.'],

        // ── Identity Security Events ────────────────────────────────────────────
        [EventType::USER_UNLOCKED, NotificationChannel::IN_APP, 'Usuario desbloqueado', 'Su cuenta ha sido desbloqueada.'],
        [EventType::USER_UNLOCKED, NotificationChannel::EMAIL, 'Usuario desbloqueado', 'Su cuenta ha sido desbloqueada. Si no solicitó esto, contacte a soporte.'],
        [EventType::PASSWORD_CHANGED, NotificationChannel::IN_APP, 'Contraseña cambiada', 'Su contraseña ha sido cambiada.'],
        [EventType::PASSWORD_CHANGED, NotificationChannel::EMAIL, 'Contraseña cambiada', 'Su contraseña ha sido cambiada. Si no realizó esta acción, contacte a soporte.'],
        [EventType::PASSWORD_RESET_REQUESTED, NotificationChannel::IN_APP, 'Restablecimiento solicitado', 'Se ha solicitado un restablecimiento de contraseña.'],
        [EventType::PASSWORD_RESET_REQUESTED, NotificationChannel::EMAIL, 'Restablecimiento de contraseña', 'Se ha solicitado restablecer su contraseña. Use el enlace para continuar.'],
        [EventType::MFA_SETUP_COMPLETED, NotificationChannel::IN_APP, 'MFA configurado', 'La autenticación de dos factores ha sido configurada.'],
        [EventType::MFA_SETUP_COMPLETED, NotificationChannel::EMAIL, 'MFA configurado', 'La autenticación de dos factores ha sido configurada en su cuenta.'],
        [EventType::MFA_DISABLED_BY_ADMIN, NotificationChannel::IN_APP, 'MFA deshabilitado', 'La autenticación de dos factores ha sido deshabilitada por un administrador.'],
        [EventType::MFA_DISABLED_BY_ADMIN, NotificationChannel::EMAIL, 'MFA deshabilitado', 'La autenticación de dos factores ha sido deshabilitada por un administrador.'],
        [EventType::EMPLOYEE_REGISTERED, NotificationChannel::IN_APP, 'Empleado registrado', 'El empleado ha sido registrado como usuario.'],
        [EventType::EMPLOYEE_REGISTERED, NotificationChannel::EMAIL, 'Empleado registrado', 'El empleado {{employeeName}} ha sido registrado como usuario del sistema.'],
        [EventType::CONTACT_LINKED, NotificationChannel::IN_APP, 'Contacto vinculado', 'El contacto ha sido vinculado a un usuario.'],
        [EventType::CONTACT_LINKED, NotificationChannel::EMAIL, 'Contacto vinculado', 'El contacto {{contactName}} ha sido vinculado a un usuario del sistema.'],
        [EventType::ADMIN_SEEDED, NotificationChannel::IN_APP, 'Administrador creado', 'Se ha creado el administrador inicial.'],
        [EventType::ADMIN_SEEDED, NotificationChannel::EMAIL, 'Administrador inicial creado', 'Se ha creado la cuenta del administrador inicial: {{username}}.'],

        // ── Organization - Employee lifecycle ─────────────────────────────────
        [EventType::EMPLOYEE_REACTIVATED, NotificationChannel::IN_APP, 'Empleado reactivado', 'El empleado ha sido reactivado.'],
        [EventType::EMPLOYEE_REACTIVATED, NotificationChannel::EMAIL, 'Empleado reactivado', 'El empleado {{firstName}} {{lastName}} ha sido reactivado.'],
        [EventType::EMPLOYEE_DELETED, NotificationChannel::IN_APP, 'Empleado eliminado', 'El empleado ha sido eliminado.'],
        [EventType::EMPLOYEE_DELETED, NotificationChannel::EMAIL, 'Empleado eliminado', 'El empleado {{firstName}} {{lastName}} ha sido eliminado.'],
        [EventType::EMPLOYEE_USER_LINKED, NotificationChannel::IN_APP, 'Usuario vinculado', 'Se ha vinculado un usuario al empleado.'],
        [EventType::EMPLOYEE_USER_LINKED, NotificationChannel::EMAIL, 'Usuario vinculado a empleado', 'Se ha vinculado el usuario {{username}} al empleado {{firstName}} {{lastName}}.'],
        [EventType::EMPLOYEE_USER_UNLINKED, NotificationChannel::IN_APP, 'Usuario desvinculado', 'Se ha desvinculado el usuario del empleado.'],
        [EventType::EMPLOYEE_USER_UNLINKED, NotificationChannel::EMAIL, 'Usuario desvinculado de empleado', 'Se ha desvinculado el usuario {{username}} del empleado {{firstName}} {{lastName}}.'],
        [EventType::EMPLOYEE_ACCESS_PROVISIONED, NotificationChannel::IN_APP, 'Acceso provisionado', 'Se ha provisionado acceso al empleado.'],
        [EventType::EMPLOYEE_ACCESS_PROVISIONED, NotificationChannel::EMAIL, 'Acceso de empleado provisionado', 'Se ha provisionado acceso al sistema para {{firstName}} {{lastName}} ({{email}}).'],

        // ── Vacation Events ────────────────────────────────────────────────────
        [EventType::VACATION_CANCELLED, NotificationChannel::IN_APP, 'Vacaciones canceladas', 'Las vacaciones han sido canceladas.'],
        [EventType::VACATION_CANCELLED, NotificationChannel::EMAIL, 'Vacaciones canceladas', 'Las vacaciones del {{startDate}} al {{endDate}} han sido canceladas.'],
        [EventType::VACATION_REJECTED, NotificationChannel::IN_APP, 'Vacaciones rechazadas', 'Las vacaciones han sido rechazadas.'],
        [EventType::VACATION_REJECTED, NotificationChannel::EMAIL, 'Vacaciones rechazadas', 'Las vacaciones del {{startDate}} al {{endDate}} han sido rechazadas. Motivo: {{reason}}.'],

        // ── Overtime Events ────────────────────────────────────────────────────
        [EventType::OVERTIME_REGISTERED, NotificationChannel::IN_APP, 'Horas extra registradas', 'Se han registrado horas extra.'],
        [EventType::OVERTIME_REGISTERED, NotificationChannel::EMAIL, 'Horas extra registradas', 'Se han registrado {{hours}} horas extra para el empleado {{employeeName}}.'],
        [EventType::OVERTIME_APPROVED, NotificationChannel::IN_APP, 'Horas extra aprobadas', 'Las horas extra han sido aprobadas.'],
        [EventType::OVERTIME_APPROVED, NotificationChannel::EMAIL, 'Horas extra aprobadas', 'Las {{hours}} horas extra del empleado {{employeeName}} han sido aprobadas.'],
        [EventType::OVERTIME_CORRECTED, NotificationChannel::IN_APP, 'Horas extra corregidas', 'Las horas extra han sido corregidas.'],
        [EventType::OVERTIME_CORRECTED, NotificationChannel::EMAIL, 'Horas extra corregidas', 'Las horas extra del empleado {{employeeName}} han sido corregidas a {{hours}} horas.'],
        [EventType::OVERTIME_REJECTED, NotificationChannel::IN_APP, 'Horas extra rechazadas', 'Las horas extra han sido rechazadas.'],
        [EventType::OVERTIME_REJECTED, NotificationChannel::EMAIL, 'Horas extra rechazadas', 'Las horas extra del empleado {{employeeName}} han sido rechazadas. Motivo: {{reason}}.'],
        [EventType::OVERTIME_REVIEW_REQUESTED, NotificationChannel::IN_APP, 'Revisión de horas extra', 'Se ha solicitado revisión de horas extra.'],
        [EventType::OVERTIME_REVIEW_REQUESTED, NotificationChannel::EMAIL, 'Revisión de horas extra solicitada', 'Se ha solicitado revisión de las horas extra del empleado {{employeeName}}.'],

        // ── Service Zone Events ────────────────────────────────────────────────
        [EventType::SERVICE_ZONE_CREATED, NotificationChannel::IN_APP, 'Zona de servicio creada', 'Se ha creado una nueva zona de servicio.'],
        [EventType::SERVICE_ZONE_CREATED, NotificationChannel::EMAIL, 'Zona de servicio creada', 'Se ha creado la zona de servicio {{zoneName}}.'],
        [EventType::SERVICE_ZONE_DEACTIVATED, NotificationChannel::IN_APP, 'Zona de servicio desactivada', 'La zona de servicio ha sido desactivada.'],
        [EventType::SERVICE_ZONE_DEACTIVATED, NotificationChannel::EMAIL, 'Zona de servicio desactivada', 'La zona de servicio {{zoneName}} ha sido desactivada.'],
        [EventType::SERVICE_ZONE_REACTIVATED, NotificationChannel::IN_APP, 'Zona de servicio reactivada', 'La zona de servicio ha sido reactivada.'],
        [EventType::SERVICE_ZONE_REACTIVATED, NotificationChannel::EMAIL, 'Zona de servicio reactivada', 'La zona de servicio {{zoneName}} ha sido reactivada.'],

        // ── Special Period Events ──────────────────────────────────────────────
        [EventType::SPECIAL_PERIOD_CANCELLED, NotificationChannel::IN_APP, 'Período especial cancelado', 'El período especial ha sido cancelado.'],
        [EventType::SPECIAL_PERIOD_CANCELLED, NotificationChannel::EMAIL, 'Período especial cancelado', 'El período especial {{periodName}} ha sido cancelado.'],
        [EventType::SPECIAL_PERIOD_COMPLETED, NotificationChannel::IN_APP, 'Período especial completado', 'El período especial ha sido completado.'],
        [EventType::SPECIAL_PERIOD_COMPLETED, NotificationChannel::EMAIL, 'Período especial completado', 'El período especial {{periodName}} ha sido completado.'],
        [EventType::SPECIAL_PERIOD_EDITED, NotificationChannel::IN_APP, 'Período especial editado', 'El período especial ha sido editado.'],
        [EventType::SPECIAL_PERIOD_EDITED, NotificationChannel::EMAIL, 'Período especial editado', 'El período especial {{periodName}} ha sido editado.'],

        // ── City Transferred ───────────────────────────────────────────────────
        [EventType::CITY_TRANSFERRED, NotificationChannel::IN_APP, 'Ciudad transferida', 'Se ha transferido una ciudad.'],
        [EventType::CITY_TRANSFERRED, NotificationChannel::EMAIL, 'Ciudad transferida', 'La ciudad {{cityName}} ha sido transferida a {{newZoneName}}.'],

        // ── Client Updated ─────────────────────────────────────────────────────
        [EventType::CLIENT_UPDATED, NotificationChannel::IN_APP, 'Cliente actualizado', 'El cliente ha sido actualizado.'],
        [EventType::CLIENT_UPDATED, NotificationChannel::EMAIL, 'Cliente actualizado', 'El cliente {{clientName}} ha sido actualizado.'],

        // ── Contact User Linked ────────────────────────────────────────────────
        [EventType::CONTACT_USER_LINKED, NotificationChannel::IN_APP, 'Contacto vinculado', 'El contacto ha sido vinculado a un usuario.'],
        [EventType::CONTACT_USER_LINKED, NotificationChannel::EMAIL, 'Contacto vinculado a usuario', 'El contacto {{contactName}} ha sido vinculado al usuario {{username}}.'],
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
