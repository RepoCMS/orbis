<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\AtmModel\EvidenceMoment;
use App\Support\Domain\Exceptions\FindingEvidenceException;
use App\Support\Domain\ValueObjects\FindingEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use DateTimeImmutable;

/**
 * Entidad que representa una evidencia (foto o video) asociada a un
 * TicketModuleFinding, capturada antes (before) o después (after) de la reparación.
 *
 * Soporta mime types image/* y video/* validados al momento de creación.
 */
final class FindingEvidence
{
    /** Hydrated by Doctrine via ManyToOne association */
    private ?TicketModuleFinding $finding = null;

    private function __construct(
        private FindingEvidenceIdValueObject $id,
        private TicketModuleFindingIdValueObject $findingId,
        private string $filePath,
        private string $originalName,
        private string $mimeType,
        private EvidenceMoment $moment,
        private string $uploadedBy,
        private DateTimeImmutable $uploadedAt,
    ) {
    }

    /**
     * Crea una nueva evidencia validando que el mime type sea imagen o video.
     *
     * @throws FindingEvidenceException Si el mime type no es image/* ni video/*
     */
    public static function upload(
        FindingEvidenceIdValueObject $id,
        TicketModuleFindingIdValueObject $findingId,
        string $filePath,
        string $originalName,
        string $mimeType,
        EvidenceMoment $moment,
        string $uploadedBy,
    ): self {
        if (!str_starts_with($mimeType, 'image/') && !str_starts_with($mimeType, 'video/')) {
            throw new FindingEvidenceException('Solo se permiten archivos de imagen o video.');
        }
        if (trim($filePath) === '') {
            throw new \InvalidArgumentException('La ruta del archivo es obligatoria.');
        }
        if (trim($originalName) === '') {
            throw new \InvalidArgumentException('El nombre original del archivo es obligatorio.');
        }
        if (trim($uploadedBy) === '') {
            throw new \InvalidArgumentException('El usuario que sube el archivo es obligatorio.');
        }

        return new self(
            id: $id,
            findingId: $findingId,
            filePath: $filePath,
            originalName: $originalName,
            mimeType: $mimeType,
            moment: $moment,
            uploadedBy: $uploadedBy,
            uploadedAt: new DateTimeImmutable(),
        );
    }

    /**
     * Asigna esta evidencia a un TicketModuleFinding (relación bidireccional).
     *
     * Llamado por TicketModuleFinding::addEvidence().
     */
    public function assignTo(TicketModuleFinding $finding): void
    {
        $this->finding = $finding;
    }

    public function id(): FindingEvidenceIdValueObject
    {
        return $this->id;
    }

    public function findingId(): TicketModuleFindingIdValueObject
    {
        return $this->findingId;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function moment(): EvidenceMoment
    {
        return $this->moment;
    }

    public function uploadedBy(): string
    {
        return $this->uploadedBy;
    }

    public function uploadedAt(): DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function finding(): ?TicketModuleFinding
    {
        return $this->finding;
    }
}
