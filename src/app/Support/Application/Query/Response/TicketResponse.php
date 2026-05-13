<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;

final class TicketResponse
{
    private static array $statusLabels = [
        'created'          => 'Creado',
        'assigned'         => 'Asignado',
        'in_transit_out'   => 'En tránsito (ida)',
        'waiting'          => 'En espera',
        'in_assistance'    => 'En asistencia',
        'paused'           => 'Pausado',
        'in_transit_back'  => 'En tránsito (vuelta)',
        'pending_closure'  => 'Pendiente de cierre',
        'closed'           => 'Cerrado',
        'cancelled'        => 'Cancelado',
    ];

    private static array $serviceTypeLabels = [
        'preaslistamiento' => 'Pre-alistamiento',
        'instalacion'      => 'Instalación',
        'desinstalacion'   => 'Desinstalación',
        'visita'           => 'Visita',
        'activacion'       => 'Activación',
        'desactivacion'    => 'Desactivación',
        'correctivo'       => 'Correctivo',
        'mantenimiento'    => 'Mantenimiento',
    ];

    public function __construct(
        public readonly string  $id,
        public readonly string  $ticketNumber,
        public readonly string  $clientId,
        public readonly string  $terminalAssignmentId,
        public readonly string  $serviceType,
        public readonly string  $serviceTypeLabel,
        public readonly string  $status,
        public readonly string  $statusLabel,
        public readonly string  $description,
        public readonly string  $createdBy,
        public readonly ?string $scheduledAt,
        public readonly ?string $slaDeadlineAt,
        public readonly ?string $closedAt,
        public readonly ?string $cancelledAt,
        public readonly ?string $cancellationReason,
    ) {}

    public static function fromEntity(\App\Support\Domain\Entities\Ticket $ticket): self
    {
        $serviceTypeVal = $ticket->serviceType()->value;
        $statusVal      = $ticket->status()->value;

        return new self(
            id:                   (string) $ticket->id(),
            ticketNumber:         $ticket->ticketNumber(),
            clientId:             $ticket->clientId(),
            terminalAssignmentId: $ticket->terminalAssignmentId(),
            serviceType:          $serviceTypeVal,
            serviceTypeLabel:     self::$serviceTypeLabels[$serviceTypeVal] ?? $serviceTypeVal,
            status:               $statusVal,
            statusLabel:          self::$statusLabels[$statusVal] ?? $statusVal,
            description:          $ticket->description(),
            createdBy:            $ticket->createdBy(),
            scheduledAt:          $ticket->scheduledAt()?->format('Y-m-d H:i'),
            slaDeadlineAt:        $ticket->slaDeadlineAt()?->format('Y-m-d H:i'),
            closedAt:             $ticket->closedAt()?->format('Y-m-d H:i'),
            cancelledAt:          $ticket->cancelledAt()?->format('Y-m-d H:i'),
            cancellationReason:   $ticket->cancellationReason(),
        );
    }
}
