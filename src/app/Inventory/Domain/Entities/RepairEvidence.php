<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\EvidenceType;
use App\Inventory\Domain\ValueObjects\RepairEvidenceIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use DateTimeImmutable;

/**
 * Evidencia fotográfica/video adjunta a una reparación.
 *
 * Solo uso interno — el laboratorista adjunta evidencia para documentar
 * el proceso de reparación (fotos del estado, video de pruebas, etc.).
 */
final class RepairEvidence
{
    private function __construct(
        private RepairEvidenceIdValueObject $id,
        private RepairIdValueObject $repairId,
        private string $filePath,
        private EvidenceType $type,
        private string $uploadedBy,
        private ?string $notes,
        private DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * Registra una nueva evidencia para una reparación.
     */
    public static function upload(
        RepairEvidenceIdValueObject $id,
        RepairIdValueObject $repairId,
        string $filePath,
        EvidenceType $type,
        string $uploadedBy,
        ?string $notes = null,
    ): self {
        return new self(
            id:         $id,
            repairId:   $repairId,
            filePath:   $filePath,
            type:       $type,
            uploadedBy: $uploadedBy,
            notes:      $notes,
            createdAt:  new DateTimeImmutable(),
        );
    }

    // ─── Getters ──────────────────────────────────────────────────────────────

    public function id(): RepairEvidenceIdValueObject
    {
        return $this->id;
    }

    public function repairId(): RepairIdValueObject
    {
        return $this->repairId;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function type(): EvidenceType
    {
        return $this->type;
    }

    public function uploadedBy(): string
    {
        return $this->uploadedBy;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
