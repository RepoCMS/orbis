<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\Enums\MaintenanceFrequencyType;
use App\Support\Domain\ValueObjects\ScheduledMaintenanceIdValueObject;

/**
 * Mantenimiento preventivo programado.
 *
 * Representa una configuración de mantenimiento recurrente para un terminal.
 * El sistema evalúa periódicamente si se debe generar un ticket de mantenimiento.
 */
final class ScheduledMaintenance
{
    private const int ALERT_DAYS_BEFORE = 7;

    private function __construct(
        private ScheduledMaintenanceIdValueObject $id,
        private string                            $clientId,
        private string                            $terminalAssignmentId,
        private string                            $modelId,
        private MaintenanceFrequencyType          $frequency,
        private \DateTimeImmutable                $nextScheduledAt,
        private ?\DateTimeImmutable               $lastCompletedAt,
        private bool                              $isActive,
        private string                            $createdBy,
        private \DateTimeImmutable                $createdAt,
        private ?\DateTimeImmutable               $updatedAt,
        private ?\DateTimeImmutable               $deletedAt,
    ) {}

    public static function create(
        ScheduledMaintenanceIdValueObject $id,
        string $clientId,
        string $terminalAssignmentId,
        string $modelId,
        MaintenanceFrequencyType $frequency,
        string $createdBy,
        ?\DateTimeImmutable $nextScheduledAt = null,
    ): self {
        if (trim($clientId) === '') {
            throw new \InvalidArgumentException('El ID del cliente es obligatorio.');
        }
        if (trim($terminalAssignmentId) === '') {
            throw new \InvalidArgumentException('El ID de asignación de terminal es obligatorio.');
        }
        if (trim($modelId) === '') {
            throw new \InvalidArgumentException('El ID del modelo es obligatorio.');
        }
        if (trim($createdBy) === '') {
            throw new \InvalidArgumentException('El usuario creador es obligatorio.');
        }

        return new self(
            id:                   $id,
            clientId:             trim($clientId),
            terminalAssignmentId: trim($terminalAssignmentId),
            modelId:              trim($modelId),
            frequency:            $frequency,
            nextScheduledAt:      $nextScheduledAt ?? (new \DateTimeImmutable())->modify('+1 month'),
            lastCompletedAt:      null,
            isActive:             true,
            createdBy:            trim($createdBy),
            createdAt:            new \DateTimeImmutable(),
            updatedAt:            null,
            deletedAt:            null,
        );
    }

    /**
     * Calcula la próxima fecha basada en la frecuencia.
     * Se llama después de completar un ciclo de mantenimiento.
     */
    public function scheduleNext(): void
    {
        $this->lastCompletedAt = new \DateTimeImmutable();
        $this->nextScheduledAt = (new \DateTimeImmutable())->add($this->frequency->toDateInterval());
        $this->markAsUpdated();
    }

    /**
     * Determina si la fecha de mantenimiento está próxima (dentro de los días de alerta).
     */
    public function isDueSoon(\DateTimeImmutable $referenceDate = new \DateTimeImmutable()): bool
    {
        if (!$this->isActive || $this->deletedAt !== null) {
            return false;
        }

        $diffDays = $this->nextScheduledAt->diff($referenceDate)->days;

        // Está próxima si la fecha está dentro de los próximos ALERT_DAYS_BEFORE días
        // o si ya pasó (vencida)
        return $referenceDate >= $this->nextScheduledAt->modify("-" . self::ALERT_DAYS_BEFORE . " days");
    }

    /**
     * Determina si la fecha de mantenimiento ha llegado o está vencida.
     */
    public function isDue(\DateTimeImmutable $referenceDate = new \DateTimeImmutable()): bool
    {
        if (!$this->isActive || $this->deletedAt !== null) {
            return false;
        }

        return $referenceDate >= $this->nextScheduledAt;
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->markAsUpdated();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->markAsUpdated();
    }

    public function updateNextScheduledAt(\DateTimeImmutable $nextScheduledAt): void
    {
        $this->nextScheduledAt = $nextScheduledAt;
        $this->markAsUpdated();
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): ScheduledMaintenanceIdValueObject { return $this->id; }
    public function clientId(): string                       { return $this->clientId; }
    public function terminalAssignmentId(): string            { return $this->terminalAssignmentId; }
    public function modelId(): string                         { return $this->modelId; }
    public function frequency(): MaintenanceFrequencyType     { return $this->frequency; }
    public function nextScheduledAt(): \DateTimeImmutable     { return $this->nextScheduledAt; }
    public function lastCompletedAt(): ?\DateTimeImmutable    { return $this->lastCompletedAt; }
    public function isActive(): bool                          { return $this->isActive; }
    public function createdBy(): string                       { return $this->createdBy; }
    public function createdAt(): \DateTimeImmutable           { return $this->createdAt; }
    public function updatedAt(): ?\DateTimeImmutable          { return $this->updatedAt; }
    public function deletedAt(): ?\DateTimeImmutable          { return $this->deletedAt; }
    public function alertDaysBefore(): int                    { return self::ALERT_DAYS_BEFORE; }
}
