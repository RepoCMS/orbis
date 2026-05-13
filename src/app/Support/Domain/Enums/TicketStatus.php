<?php

declare(strict_types=1);

namespace App\Support\Domain\Enums;

enum TicketStatus: string
{
    case CREATED          = 'CREATED';
    case ASSIGNED         = 'ASSIGNED';
    case IN_TRANSIT_OUT   = 'IN_TRANSIT_OUT';
    case WAITING          = 'WAITING';
    case IN_ASSISTANCE    = 'IN_ASSISTANCE';
    case PAUSED           = 'PAUSED';
    case IN_TRANSIT_BACK  = 'IN_TRANSIT_BACK';
    case PENDING_CLOSURE  = 'PENDING_CLOSURE';
    case CLOSED           = 'CLOSED';
    case CANCELLED        = 'CANCELLED';

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
