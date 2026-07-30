<?php

declare(strict_types=1);

namespace App\Notification\Domain\Enums;

enum EventType: string
{
    case TICKET_CREATED = 'ticket_created';
    case TICKET_ASSIGNED = 'ticket_assigned';
    case TICKET_REASSIGNED = 'ticket_reassigned';
    case TICKET_RESCHEDULED = 'ticket_rescheduled';
    case TICKET_CANCELLED = 'ticket_cancelled';
    case TICKET_CLOSED = 'ticket_closed';
    case EXPENSE_ITEM_REJECTED = 'expense_item_rejected';
    case EXPENSE_APPROVED = 'expense_approved';
    case EXPENSE_SUBMITTED = 'expense_submitted';
    case EXPENSE_RESUBMITTED = 'expense_resubmitted';
    case CASH_ADVANCE_CREATED = 'cash_advance_created';
    case CASH_ADVANCE_UPDATED = 'cash_advance_updated';
    case CASH_ADVANCE_PENDING_PAYMENT = 'cash_advance_pending_payment';
    case CASH_ADVANCE_PENDING_REVIEW = 'cash_advance_pending_review';
    case CASH_ADVANCE_SETTLED = 'cash_advance_settled';
    case USER_REGISTERED = 'user_registered';
    case USER_ACCOUNT_ACTIVATED = 'user_account_activated';
    case USER_PASSWORD_RESET = 'user_password_reset';
    case STOCK_BELOW_THRESHOLD = 'stock_below_threshold';
    case EMERGENCY_PART_USED = 'emergency_part_used';
    case PART_REPAIRED = 'part_repaired';
    case EMPLOYEE_SPECIAL_PERIOD_STARTING = 'employee_special_period_starting';
    case SLA_ABOUT_TO_EXPIRE = 'sla_about_to_expire';
    case SLA_BREACHED = 'sla_breached';
    case WARRANTY_EXPIRING_SOON = 'warranty_expiring_soon';
    case REQUEST_SUBMITTED = 'request_submitted';
    case REQUEST_AWAITING_TECHNICIAN_ACCEPTANCE = 'request_awaiting_technician_acceptance';
    case REQUEST_REJECTED = 'request_rejected';
    case REQUEST_CANCELLED = 'request_cancelled';
    case REQUEST_FULFILLED = 'request_fulfilled';

    // Client
    // Support - Ticket lifecycle extended
    case TICKET_ARRIVED_AT_SITE = 'ticket_arrived_at_site';
    case TICKET_ASSISTANCE_STARTED = 'ticket_assistance_started';
    case TICKET_ASSISTANCE_COMPLETED = 'ticket_assistance_completed';
    case TICKET_FINDING_REGISTERED = 'ticket_finding_registered';
    case TICKET_JOURNEY_STARTED = 'ticket_journey_started';
    case TICKET_PAUSED = 'ticket_paused';
    case TICKET_WAITING_STARTED = 'ticket_waiting_started';
    case TICKET_RESUMED = 'ticket_resumed';
    case TICKET_REOPEN_REQUESTED = 'ticket_reopen_requested';
    case TICKET_REOPENED = 'ticket_reopened';

    // Inventory - Stock and products
    case REQUEST_APPROVED = 'request_approved';
    case STOCK_MOVED = 'stock_moved';
    case SERIALIZED_STOCK_CREATED = 'serialized_stock_created';
    case ENTRY_CONFIRMED = 'entry_confirmed';
    case PRODUCT_CREATED = 'product_created';

    // Organization - Employee lifecycle
    case EMPLOYEE_CREATED = 'employee_created';
    case EMPLOYEE_DEACTIVATED = 'employee_deactivated';
    case EMPLOYEE_REACTIVATED = 'employee_reactivated';
    case EMPLOYEE_DELETED = 'employee_deleted';
    case EMPLOYEE_USER_LINKED = 'employee_user_linked';
    case EMPLOYEE_USER_UNLINKED = 'employee_user_unlinked';
    case EMPLOYEE_ACCESS_PROVISIONED = 'employee_access_provisioned';
    case VACATION_REQUESTED = 'vacation_requested';
    case VACATION_APPROVED = 'vacation_approved';
    case VACATION_CANCELLED = 'vacation_cancelled';
    case VACATION_REJECTED = 'vacation_rejected';
    case OVERTIME_REGISTERED = 'overtime_registered';
    case OVERTIME_APPROVED = 'overtime_approved';
    case OVERTIME_CORRECTED = 'overtime_corrected';
    case OVERTIME_REJECTED = 'overtime_rejected';
    case OVERTIME_REVIEW_REQUESTED = 'overtime_review_requested';
    case SERVICE_ZONE_CREATED = 'service_zone_created';
    case SERVICE_ZONE_DEACTIVATED = 'service_zone_deactivated';
    case SERVICE_ZONE_REACTIVATED = 'service_zone_reactivated';
    case SPECIAL_PERIOD_CANCELLED = 'special_period_cancelled';
    case SPECIAL_PERIOD_COMPLETED = 'special_period_completed';
    case SPECIAL_PERIOD_EDITED = 'special_period_edited';
    case CITY_TRANSFERRED = 'city_transferred';

    // Client - Core events
    case CLIENT_CREATED = 'client_created';
    case CONTACT_ADDED = 'contact_added';

    case CLIENT_DEACTIVATED = 'client_deactivated';
    case CLIENT_REACTIVATED = 'client_reactivated';
    case CLIENT_DELETED = 'client_deleted';
    case CLIENT_UPDATED = 'client_updated';
    case CLIENT_SALE_ORDER_CREATED = 'client_sale_order_created';
    case CLIENT_SALE_ORDER_COMPLETED = 'client_sale_order_completed';
    case CONTACT_UPDATED = 'contact_updated';
    case CONTACT_DEACTIVATED = 'contact_deactivated';
    case CONTACT_REACTIVATED = 'contact_reactivated';
    case CONTACT_USER_LINKED = 'contact_user_linked';
    case CONTACT_USER_UNLINKED = 'contact_user_unlinked';
    case CONTACT_ACCESS_PROVISIONED = 'contact_access_provisioned';
    case TERMINAL_REGISTERED = 'terminal_registered';
    case TERMINAL_UPDATED = 'terminal_updated';
    case TERMINAL_DEACTIVATED = 'terminal_deactivated';
    case TERMINAL_REACTIVATED = 'terminal_reactivated';
    case TERMINAL_ASSIGNMENT_REGISTERED = 'terminal_assignment_registered';
    case TERMINAL_ASSIGNMENT_UPDATED = 'terminal_assignment_updated';
    case TERMINAL_ASSIGNMENT_DEACTIVATED = 'terminal_assignment_deactivated';
    case TERMINAL_ASSIGNMENT_REACTIVATED = 'terminal_assignment_reactivated';
    case CONTRACT_CREATED = 'contract_created';
    case CONTRACT_UPDATED = 'contract_updated';
    case CONTRACT_CANCELLED = 'contract_cancelled';
    case WARRANTY_REGISTERED = 'warranty_registered';
    case WARRANTY_UPDATED = 'warranty_updated';
    case WARRANTY_EXPIRED = 'warranty_expired';
    case SLA_POLICY_CREATED = 'sla_policy_created';

    // Identity - Security and lifecycle
    case USER_UNLOCKED = 'user_unlocked';
    case PASSWORD_CHANGED = 'password_changed';
    case PASSWORD_RESET_REQUESTED = 'password_reset_requested';
    case MFA_SETUP_COMPLETED = 'mfa_setup_completed';
    case MFA_DISABLED_BY_ADMIN = 'mfa_disabled_by_admin';
    case ROLE_CREATED = 'role_created';
    case ROLE_DELETED = 'role_deleted';
    case ROLE_PERMISSIONS_UPDATED = 'role_permissions_updated';
    case EMPLOYEE_REGISTERED = 'employee_registered';
    case CONTACT_LINKED = 'contact_linked';
    case ADMIN_SEEDED = 'admin_seeded';

    public function label(): string
    {
        return match ($this) {
            self::TICKET_CREATED => 'Ticket creado',
            self::TICKET_ASSIGNED => 'Ticket asignado',
            self::TICKET_REASSIGNED => 'Ticket reasignado',
            self::TICKET_RESCHEDULED => 'Ticket reprogramado',
            self::TICKET_CANCELLED => 'Ticket cancelado',
            self::TICKET_CLOSED => 'Ticket cerrado',
            self::EXPENSE_ITEM_REJECTED => 'Gasto rechazado',
            self::EXPENSE_APPROVED => 'Liquidación aprobada',
            self::EXPENSE_SUBMITTED => 'Liquidación enviada',
            self::EXPENSE_RESUBMITTED => 'Liquidación reenviada',
            self::CASH_ADVANCE_CREATED => 'Adelanto creado',
            self::CASH_ADVANCE_UPDATED => 'Adelanto actualizado',
            self::CASH_ADVANCE_PENDING_PAYMENT => 'Adelanto pendiente de pago',
            self::CASH_ADVANCE_PENDING_REVIEW => 'Adelanto pendiente de revisión',
            self::CASH_ADVANCE_SETTLED => 'Adelanto liquidado',
            self::USER_REGISTERED => 'Usuario registrado',
            self::USER_ACCOUNT_ACTIVATED => 'Cuenta activada',
            self::USER_PASSWORD_RESET => 'Contraseña restablecida',
            self::STOCK_BELOW_THRESHOLD => 'Stock por debajo del umbral',
            self::EMERGENCY_PART_USED => 'Repuesto de emergencia usado',
            self::PART_REPAIRED => 'Repuesto reparado',
            self::EMPLOYEE_SPECIAL_PERIOD_STARTING => 'Periodo especial de empleado próximo',
            self::SLA_ABOUT_TO_EXPIRE => 'SLA próximo a vencer',
            self::SLA_BREACHED => 'SLA vencido',
            self::WARRANTY_EXPIRING_SOON => 'Garantía próxima a vencer',
            self::REQUEST_SUBMITTED => 'Nueva solicitud de repuesto',
            self::REQUEST_AWAITING_TECHNICIAN_ACCEPTANCE => 'Solicitud aprobada',
            self::REQUEST_REJECTED => 'Solicitud rechazada',
            self::REQUEST_CANCELLED => 'Solicitud cancelada',
            self::REQUEST_FULFILLED => 'Solicitud ejecutada',
            self::TICKET_ARRIVED_AT_SITE => 'Técnico llegó al sitio',
            self::TICKET_ASSISTANCE_STARTED => 'Asistencia iniciada',
            self::TICKET_ASSISTANCE_COMPLETED => 'Asistencia completada',
            self::TICKET_FINDING_REGISTERED => 'Hallazgo registrado',
            self::TICKET_JOURNEY_STARTED => 'Viaje iniciado',
            self::TICKET_PAUSED => 'Ticket pausado',
            self::TICKET_WAITING_STARTED => 'Espera iniciada',
            self::TICKET_RESUMED => 'Ticket reanudado',
            self::TICKET_REOPEN_REQUESTED => 'Solicitud de reapertura',
            self::TICKET_REOPENED => 'Ticket reabierto',
            self::REQUEST_APPROVED => 'Solicitud de repuesto aprobada',
            self::STOCK_MOVED => 'Movimiento de stock completado',
            self::SERIALIZED_STOCK_CREATED => 'Stock serializado registrado',
            self::ENTRY_CONFIRMED => 'Entrada de inventario confirmada',
            self::PRODUCT_CREATED => 'Producto creado',
            self::EMPLOYEE_CREATED => 'Empleado creado',
            self::EMPLOYEE_DEACTIVATED => 'Empleado desactivado',
            self::EMPLOYEE_REACTIVATED => 'Empleado reactivado',
            self::EMPLOYEE_DELETED => 'Empleado eliminado',
            self::EMPLOYEE_USER_LINKED => 'Usuario vinculado a empleado',
            self::EMPLOYEE_USER_UNLINKED => 'Usuario desvinculado de empleado',
            self::EMPLOYEE_ACCESS_PROVISIONED => 'Acceso de empleado provisionado',
            self::VACATION_REQUESTED => 'Vacaciones solicitadas',
            self::VACATION_APPROVED => 'Vacaciones aprobadas',
            self::VACATION_CANCELLED => 'Vacaciones canceladas',
            self::VACATION_REJECTED => 'Vacaciones rechazadas',
            self::OVERTIME_REGISTERED => 'Horas extra registradas',
            self::OVERTIME_APPROVED => 'Horas extra aprobadas',
            self::OVERTIME_CORRECTED => 'Horas extra corregidas',
            self::OVERTIME_REJECTED => 'Horas extra rechazadas',
            self::OVERTIME_REVIEW_REQUESTED => 'Revisión de horas extra solicitada',
            self::SERVICE_ZONE_CREATED => 'Zona de servicio creada',
            self::SERVICE_ZONE_DEACTIVATED => 'Zona de servicio desactivada',
            self::SERVICE_ZONE_REACTIVATED => 'Zona de servicio reactivada',
            self::SPECIAL_PERIOD_CANCELLED => 'Período especial cancelado',
            self::SPECIAL_PERIOD_COMPLETED => 'Período especial completado',
            self::SPECIAL_PERIOD_EDITED => 'Período especial editado',
            self::CITY_TRANSFERRED => 'Ciudad transferida',
            self::CLIENT_CREATED => 'Cliente creado',
            self::CONTACT_ADDED => 'Contacto agregado',
            self::CLIENT_DEACTIVATED => 'Cliente desactivado',
            self::CLIENT_REACTIVATED => 'Cliente reactivado',
            self::CLIENT_DELETED => 'Cliente eliminado',
            self::CLIENT_UPDATED => 'Cliente actualizado',
            self::CLIENT_SALE_ORDER_CREATED => 'Orden de venta creada',
            self::CLIENT_SALE_ORDER_COMPLETED => 'Orden de venta completada',
            self::CONTACT_UPDATED => 'Contacto actualizado',
            self::CONTACT_DEACTIVATED => 'Contacto desactivado',
            self::CONTACT_REACTIVATED => 'Contacto reactivado',
            self::CONTACT_USER_LINKED => 'Contacto vinculado a usuario',
            self::CONTACT_USER_UNLINKED => 'Contacto desvinculado de usuario',
            self::CONTACT_ACCESS_PROVISIONED => 'Acceso de contacto provisionado',
            self::TERMINAL_REGISTERED => 'Terminal registrada',
            self::TERMINAL_UPDATED => 'Terminal actualizada',
            self::TERMINAL_DEACTIVATED => 'Terminal desactivada',
            self::TERMINAL_REACTIVATED => 'Terminal reactivada',
            self::TERMINAL_ASSIGNMENT_REGISTERED => 'Asignación de terminal registrada',
            self::TERMINAL_ASSIGNMENT_UPDATED => 'Asignación de terminal actualizada',
            self::TERMINAL_ASSIGNMENT_DEACTIVATED => 'Asignación de terminal desactivada',
            self::TERMINAL_ASSIGNMENT_REACTIVATED => 'Asignación de terminal reactivada',
            self::CONTRACT_CREATED => 'Contrato creado',
            self::CONTRACT_UPDATED => 'Contrato actualizado',
            self::CONTRACT_CANCELLED => 'Contrato cancelado',
            self::WARRANTY_REGISTERED => 'Garantía registrada',
            self::WARRANTY_UPDATED => 'Garantía actualizada',
            self::WARRANTY_EXPIRED => 'Garantía vencida',
            self::SLA_POLICY_CREATED => 'Política de SLA creada',
            self::USER_UNLOCKED => 'Usuario desbloqueado',
            self::PASSWORD_CHANGED => 'Contraseña cambiada',
            self::PASSWORD_RESET_REQUESTED => 'Restablecimiento de contraseña solicitado',
            self::MFA_SETUP_COMPLETED => 'MFA configurado',
            self::MFA_DISABLED_BY_ADMIN => 'MFA deshabilitado por administrador',
            self::ROLE_CREATED => 'Rol creado',
            self::ROLE_DELETED => 'Rol eliminado',
            self::ROLE_PERMISSIONS_UPDATED => 'Permisos de rol actualizados',
            self::EMPLOYEE_REGISTERED => 'Empleado registrado como usuario',
            self::CONTACT_LINKED => 'Contacto vinculado a usuario',
            self::ADMIN_SEEDED => 'Administrador inicial creado',
        };
    }

    public function isSecurityEvent(): bool
    {
        return match ($this) {
            self::USER_ACCOUNT_ACTIVATED,
            self::USER_PASSWORD_RESET,
            self::USER_UNLOCKED,
            self::PASSWORD_CHANGED,
            self::MFA_SETUP_COMPLETED,
            self::MFA_DISABLED_BY_ADMIN,
                => true,
            default => false,
        };
    }
}
