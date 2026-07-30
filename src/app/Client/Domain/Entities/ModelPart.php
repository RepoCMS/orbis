<?php

declare(strict_types=1);

namespace App\Client\Domain\Entities;

use App\Client\Domain\Exceptions\ModelPartNumberException;
use App\Client\Domain\ValueObjects\ModelPartIdValueObject;
use DateTimeImmutable;

final class ModelPart
{
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        private ModelPartIdValueObject $id,
        private string $model,
        private string $partName,
        private string $partNumber,
        private int $level,
        private ?string $parentPartNumber,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
    ) {}

    public static function register(
        ModelPartIdValueObject $id,
        string $model,
        string $partName,
        string $partNumber,
        int $level = 0,
        ?string $parentPartNumber = null,
    ): self {
        if (trim($model) === '') {
            throw new ModelPartNumberException('El modelo es obligatorio.');
        }
        if (trim($partName) === '') {
            throw new ModelPartNumberException('El nombre de la parte es obligatorio.');
        }
        if (trim($partNumber) === '') {
            throw new ModelPartNumberException('El número de parte es obligatorio.');
        }
        if ($level < 0) {
            throw new ModelPartNumberException('El nivel no puede ser negativo.');
        }

        return new self(
            id: $id,
            model: trim($model),
            partName: trim($partName),
            partNumber: trim($partNumber),
            level: $level,
            parentPartNumber: $parentPartNumber !== null ? trim($parentPartNumber) : null,
            createdAt: new DateTimeImmutable(),
            updatedAt: null,
        );
    }

    public function id(): ModelPartIdValueObject { return $this->id; }
    public function model(): string { return $this->model; }
    public function partName(): string { return $this->partName; }
    public function partNumber(): string { return $this->partNumber; }
    public function level(): int { return $this->level; }
    public function parentPartNumber(): ?string { return $this->parentPartNumber; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
    public function deletedAt(): ?DateTimeImmutable { return $this->deletedAt; }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
