<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\Enums\EvidenceVisibility;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final class TicketEvidence
{
    public function __construct(
        private TicketEvidenceIdValueObject $id,
        private TicketIdValueObject         $ticketId,
        private string                      $filePath,
        private string                      $uploadedBy,
        private \DateTimeImmutable          $uploadedAt,
        private EvidenceVisibility          $visibility = EvidenceVisibility::ALL,
    ) {}

    public static function upload(
        TicketEvidenceIdValueObject $id,
        TicketIdValueObject $ticketId,
        string $filePath,
        string $uploadedBy,
        ?EvidenceVisibility $visibility = null,
    ): self {
        if (trim($filePath) === '') {
            throw new \InvalidArgumentException('La ruta del archivo es obligatoria.');
        }
        if (trim($uploadedBy) === '') {
            throw new \InvalidArgumentException('El usuario que sube la evidencia es obligatorio.');
        }

        return new self($id, $ticketId, $filePath, $uploadedBy, new \DateTimeImmutable(), $visibility ?? EvidenceVisibility::ALL);
    }

    public function id(): TicketEvidenceIdValueObject { return $this->id; }
    public function ticketId(): TicketIdValueObject    { return $this->ticketId; }
    public function filePath(): string                 { return $this->filePath; }
    public function uploadedBy(): string               { return $this->uploadedBy; }
    public function uploadedAt(): \DateTimeImmutable   { return $this->uploadedAt; }
    public function visibility(): EvidenceVisibility   { return $this->visibility; }
}
