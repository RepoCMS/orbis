<?php

declare(strict_types=1);

namespace App\Client\Domain\Entities;

use App\Client\Domain\Exceptions\TerminalModelNameException;
use App\Client\Domain\ValueObjects\TerminalModelIdValueObject;
use DateTimeImmutable;

final class TerminalModel
{
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        private TerminalModelIdValueObject $id,
        private string $name,
        private ?string $description,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
    ) {
    }

    public static function register(
        TerminalModelIdValueObject $id,
        string $name,
        ?string $description = null,
    ): self {
        if (trim($name) === '') {
            throw new TerminalModelNameException('El nombre del modelo es obligatorio.');
        }

        return new self(
            id: $id,
            name: trim($name),
            description: $description !== null ? (trim($description) ?: null) : null,
            createdAt: new DateTimeImmutable(),
            updatedAt: null,
        );
    }

    public function id(): TerminalModelIdValueObject { return $this->id; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
    public function deletedAt(): ?DateTimeImmutable { return $this->deletedAt; }

    public function rename(string $name): void
    {
        if (trim($name) === '') {
            throw new TerminalModelNameException('El nombre del modelo es obligatorio.');
        }
        $this->name = trim($name);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateDescription(?string $description): void
    {
        $this->description = $description !== null ? (trim($description) ?: null) : null;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
