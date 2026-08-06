<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use App\Support\Domain\ValueObjects\QuickTemplateItemIdValueObject;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class QuickTemplate
{
    private Collection $items;

    private function __construct(
        private QuickTemplateIdValueObject $id,
        private string                     $modelId,
        private string                     $name,
        private ?string                    $description,
        private bool                       $isActive,
        private string                     $createdBy,
        private \DateTimeImmutable         $createdAt,
        private ?\DateTimeImmutable        $updatedAt,
    ) {
        $this->items = new ArrayCollection();
    }

    public static function create(
        QuickTemplateIdValueObject $id,
        string $modelId,
        string $name,
        string $createdBy,
        ?string $description = null,
    ): self {
        if (trim($modelId) === '') {
            throw new \InvalidArgumentException('El ID del modelo de terminal es obligatorio.');
        }

        if (trim($name) === '') {
            throw new \InvalidArgumentException('El nombre de la plantilla es obligatorio.');
        }

        if (trim($createdBy) === '') {
            throw new \InvalidArgumentException('El creador de la plantilla es obligatorio.');
        }

        return new self(
            id:          $id,
            modelId:     trim($modelId),
            name:        trim($name),
            description: $description !== null ? (trim($description) ?: null) : null,
            isActive:    true,
            createdBy:   trim($createdBy),
            createdAt:   new \DateTimeImmutable(),
            updatedAt:   null,
        );
    }

    public function addItem(
        QuickTemplateItemIdValueObject $id,
        ?string $modelPartId,
        ?string $partNumber,
        ?string $productCode,
        string $description,
        int $quantity = 1,
        ?string $notes = null,
        int $sortOrder = 0,
    ): void {
        $item = QuickTemplateItem::register(
            id:          $id,
            modelPartId: $modelPartId,
            partNumber:  $partNumber,
            productCode: $productCode,
            description: $description,
            quantity:    $quantity,
            notes:       $notes,
            sortOrder:   $sortOrder,
        );

        $this->items->add($item);
        $this->markAsUpdated();
    }

    public function removeItem(QuickTemplateItemIdValueObject $itemId): void
    {
        foreach ($this->items as $item) {
            if ((string) $item->id() === (string) $itemId) {
                $this->items->removeElement($item);
                $this->markAsUpdated();

                return;
            }
        }

        throw new \InvalidArgumentException('Item no encontrado en la plantilla.');
    }

    public function updateItem(
        QuickTemplateItemIdValueObject $itemId,
        ?string $modelPartId,
        ?string $partNumber,
        ?string $productCode,
        string $description,
        int $quantity,
        ?string $notes,
        int $sortOrder,
    ): void {
        foreach ($this->items as $item) {
            if ((string) $item->id() === (string) $itemId) {
                $item->update(
                    modelPartId: $modelPartId,
                    partNumber:  $partNumber,
                    productCode: $productCode,
                    description: $description,
                    quantity:    $quantity,
                    notes:       $notes,
                    sortOrder:   $sortOrder,
                );
                $this->markAsUpdated();

                return;
            }
        }

        throw new \InvalidArgumentException('Item no encontrado en la plantilla.');
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

    public function rename(string $name): void
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('El nombre de la plantilla es obligatorio.');
        }

        $this->name = trim($name);
        $this->markAsUpdated();
    }

    public function updateDescription(?string $description): void
    {
        $this->description = $description !== null ? (trim($description) ?: null) : null;
        $this->markAsUpdated();
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): QuickTemplateIdValueObject
    {
        return $this->id;
    }
    public function modelId(): string
    {
        return $this->modelId;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function description(): ?string
    {
        return $this->description;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function createdBy(): string
    {
        return $this->createdBy;
    }
    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return QuickTemplateItem[] */
    public function items(): array
    {
        return $this->items->toArray();
    }
}
