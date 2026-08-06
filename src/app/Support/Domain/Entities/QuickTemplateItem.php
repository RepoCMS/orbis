<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\QuickTemplateItemIdValueObject;

final class QuickTemplateItem
{
    private function __construct(
        private QuickTemplateItemIdValueObject $id,
        private ?string                        $modelPartId,
        private ?string                        $partNumber,
        private ?string                        $productCode,
        private string                         $description,
        private int                            $quantity,
        private ?string                        $notes,
        private int                            $sortOrder,
        private \DateTimeImmutable             $createdAt,
    ) {
    }

    public static function register(
        QuickTemplateItemIdValueObject $id,
        ?string $modelPartId,
        ?string $partNumber,
        ?string $productCode,
        string $description,
        int $quantity = 1,
        ?string $notes = null,
        int $sortOrder = 0,
    ): self {
        if (trim($description) === '') {
            throw new \InvalidArgumentException('La descripción del item es obligatoria.');
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1.');
        }

        if ($sortOrder < 0) {
            throw new \InvalidArgumentException('El orden debe ser un valor no negativo.');
        }

        return new self(
            id:          $id,
            modelPartId: $modelPartId !== null ? (trim($modelPartId) ?: null) : null,
            partNumber:  $partNumber !== null ? (trim($partNumber) ?: null) : null,
            productCode: $productCode !== null ? (trim($productCode) ?: null) : null,
            description: trim($description),
            quantity:    $quantity,
            notes:       $notes !== null ? (trim($notes) ?: null) : null,
            sortOrder:   $sortOrder,
            createdAt:   new \DateTimeImmutable(),
        );
    }

    public function update(
        ?string $modelPartId,
        ?string $partNumber,
        ?string $productCode,
        string $description,
        int $quantity,
        ?string $notes,
        int $sortOrder,
    ): void {
        if (trim($description) === '') {
            throw new \InvalidArgumentException('La descripción del item es obligatoria.');
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1.');
        }

        if ($sortOrder < 0) {
            throw new \InvalidArgumentException('El orden debe ser un valor no negativo.');
        }

        $this->modelPartId = $modelPartId !== null ? (trim($modelPartId) ?: null) : null;
        $this->partNumber  = $partNumber !== null ? (trim($partNumber) ?: null) : null;
        $this->productCode = $productCode !== null ? (trim($productCode) ?: null) : null;
        $this->description = trim($description);
        $this->quantity    = $quantity;
        $this->notes       = $notes !== null ? (trim($notes) ?: null) : null;
        $this->sortOrder   = $sortOrder;
    }

    public function id(): QuickTemplateItemIdValueObject
    {
        return $this->id;
    }
    public function modelPartId(): ?string
    {
        return $this->modelPartId;
    }
    public function partNumber(): ?string
    {
        return $this->partNumber;
    }
    public function productCode(): ?string
    {
        return $this->productCode;
    }
    public function description(): string
    {
        return $this->description;
    }
    public function quantity(): int
    {
        return $this->quantity;
    }
    public function notes(): ?string
    {
        return $this->notes;
    }
    public function sortOrder(): int
    {
        return $this->sortOrder;
    }
    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
