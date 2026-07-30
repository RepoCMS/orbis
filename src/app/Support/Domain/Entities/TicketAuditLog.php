<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\TicketAuditLogIdValueObject;

final class TicketAuditLog
{
    private function __construct(
        private TicketAuditLogIdValueObject $id,
        private string $ticketId,
        private string $fieldName,
        private ?string $oldValue,
        private ?string $newValue,
        private string $changedBy,
        private \DateTimeImmutable $changedAt,
    ) {}

    public static function record(
        TicketAuditLogIdValueObject $id,
        string $ticketId,
        string $fieldName,
        ?string $oldValue,
        ?string $newValue,
        string $changedBy,
    ): self {
        if (trim($ticketId) === '') {
            throw new \InvalidArgumentException('El ID del ticket es obligatorio.');
        }
        if (trim($fieldName) === '') {
            throw new \InvalidArgumentException('El nombre del campo es obligatorio.');
        }
        if (trim($changedBy) === '') {
            throw new \InvalidArgumentException('El usuario que realizó el cambio es obligatorio.');
        }

        return new self($id, $ticketId, $fieldName, $oldValue, $newValue, $changedBy, new \DateTimeImmutable());
    }

    public function id(): TicketAuditLogIdValueObject { return $this->id; }
    public function ticketId(): string                 { return $this->ticketId; }
    public function fieldName(): string                { return $this->fieldName; }
    public function oldValue(): ?string                { return $this->oldValue; }
    public function newValue(): ?string                { return $this->newValue; }
    public function changedBy(): string                { return $this->changedBy; }
    public function changedAt(): \DateTimeImmutable    { return $this->changedAt; }
}
