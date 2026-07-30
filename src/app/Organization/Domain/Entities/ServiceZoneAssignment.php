<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entities;

use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneAssignmentIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use DateTimeImmutable;

/**
 * Historial de asignaciones de un técnico a una zona de servicio.
 *
 * Cada vez que un técnico es asignado (o reasignado) se crea un registro
 * que captura la zona anterior (si existía), la nueva zona, quién realizó
 * la asignación y cuándo se realizó.
 */
final class ServiceZoneAssignment implements TimestampableInterface
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;

    private function __construct(
        private ServiceZoneAssignmentIdValueObject $id,
        private EmployeeIdValueObject $employeeId,
        private ServiceZoneIdValueObject $newZoneId,
        private ?ServiceZoneIdValueObject $previousZoneId = null,
        private ?string $assignedByUserId = null,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Crea un registro de asignación a zona.
     *
     * @param ServiceZoneAssignmentIdValueObject $id           ID del registro de historial
     * @param EmployeeIdValueObject             $employeeId   ID del técnico asignado
     * @param ServiceZoneIdValueObject          $newZoneId    ID de la nueva zona asignada
     * @param ServiceZoneIdValueObject|null     $previousZoneId ID de la zona anterior (null si es primera asignación)
     * @param string|null                       $assignedByUserId ULID del usuario que realizó la asignación
     */
    public static function create(
        ServiceZoneAssignmentIdValueObject $id,
        EmployeeIdValueObject $employeeId,
        ServiceZoneIdValueObject $newZoneId,
        ?ServiceZoneIdValueObject $previousZoneId = null,
        ?string $assignedByUserId = null,
    ): self {
        return new self(
            id: $id,
            employeeId: $employeeId,
            newZoneId: $newZoneId,
            previousZoneId: $previousZoneId,
            assignedByUserId: $assignedByUserId,
        );
    }

    // ── Getters ──────────────────────────────────────────────────────────────

    public function id(): ServiceZoneAssignmentIdValueObject
    {
        return $this->id;
    }

    public function employeeId(): EmployeeIdValueObject
    {
        return $this->employeeId;
    }

    public function newZoneId(): ServiceZoneIdValueObject
    {
        return $this->newZoneId;
    }

    public function previousZoneId(): ?ServiceZoneIdValueObject
    {
        return $this->previousZoneId;
    }

    public function assignedByUserId(): ?string
    {
        return $this->assignedByUserId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return null;
    }

    public function isDeleted(): bool
    {
        return false;
    }

    public function softDelete(): void
    {
        // No aplica — el historial no se elimina
    }

    public function update(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
