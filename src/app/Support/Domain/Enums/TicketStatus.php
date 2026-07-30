<?php

declare(strict_types=1);

namespace App\Support\Domain\Enums;

enum TicketStatus: string
{
    case CREATED          = 'created';
    case ASSIGNED         = 'assigned';
    case IN_TRANSIT_OUT   = 'in_transit_out';
    case WAITING          = 'waiting';
    case IN_ASSISTANCE    = 'in_assistance';
    case PAUSED           = 'paused';
    case IN_TRANSIT_BACK  = 'in_transit_back';
    case PENDING_CLOSURE  = 'pending_closure';
    case CLOSED           = 'closed';
    case CANCELLED        = 'cancelled';
    case REOPENED         = 'reopened';

    public function label(): string
    {
        return match($this) {
            self::CREATED         => 'Creado',
            self::ASSIGNED        => 'Asignado',
            self::IN_TRANSIT_OUT  => 'En Tránsito (Ida)',
            self::WAITING         => 'En Espera',
            self::IN_ASSISTANCE   => 'En Asistencia',
            self::PAUSED          => 'Pausado',
            self::IN_TRANSIT_BACK => 'En Tránsito (Regreso)',
            self::PENDING_CLOSURE => 'Pendiente Cierre',
            self::CLOSED          => 'Cerrado',
            self::CANCELLED       => 'Cancelado',
            self::REOPENED        => 'Reabierto',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::CLOSED || $this === self::CANCELLED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::CREATED,
            self::ASSIGNED,
            self::IN_TRANSIT_OUT,
            self::WAITING,
        ], true);
    }

    public function canBeRescheduled(): bool
    {
        return in_array($this, [
            self::CREATED,
            self::ASSIGNED,
        ], true);
    }
}
