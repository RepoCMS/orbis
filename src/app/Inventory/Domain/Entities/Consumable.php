<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\ValueObjects\ConsumableIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use DateTimeImmutable;

/**
 * Consumible usado en una reparación.
 *
 * Registra los materiales consumibles (cables, tornillos, pasta térmica, etc.)
 * que se descuentan del inventario del laboratorio durante una reparación.
 * Solo referencia a Product por ID (cross-BC), nunca instancia la entidad Product.
 */
final class Consumable
{
    private function __construct(
        private ConsumableIdValueObject $id,
        private RepairIdValueObject $repairId,
        private string $productId,
        private string $productCode,
        private string $productName,
        private int $quantity,
        private ?float $unitCost,
        private ?string $notes,
        private DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * Registra el uso de un consumible en una reparación.
     */
    public static function register(
        ConsumableIdValueObject $id,
        RepairIdValueObject $repairId,
        string $productId,
        string $productCode,
        string $productName,
        int $quantity,
        ?float $unitCost = null,
        ?string $notes = null,
    ): self {
        return new self(
            id:          $id,
            repairId:    $repairId,
            productId:   $productId,
            productCode: $productCode,
            productName: $productName,
            quantity:    $quantity,
            unitCost:    $unitCost,
            notes:       $notes,
            createdAt:   new DateTimeImmutable(),
        );
    }

    // ─── Getters ──────────────────────────────────────────────────────────────

    public function id(): ConsumableIdValueObject
    {
        return $this->id;
    }

    public function repairId(): RepairIdValueObject
    {
        return $this->repairId;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function productCode(): string
    {
        return $this->productCode;
    }

    public function productName(): string
    {
        return $this->productName;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitCost(): ?float
    {
        return $this->unitCost;
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
