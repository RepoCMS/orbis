<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\ValueObjects\PhysicalCountItemIdValueObject;

/**
 * Ítem individual de un conteo físico.
 *
 * Entidad hija del agregado PhysicalCount. Representa el conteo de un
 * producto específico en una ubicación. Se comporta como Value Object
 * dentro del aggregate (orphan removal, ciclo de vida manejado por el padre).
 */
final class PhysicalCountItem
{
    /** Diferencia calculada: countedQty - expectedQty */
    private int $difference;

    private function __construct(
        private PhysicalCountItemIdValueObject $id,
        private PhysicalCount $physicalCount,
        private string $productCode,
        private int $expectedQty,
        private int $countedQty,
        private ?string $notes,
    ) {
        $this->difference = $countedQty - $expectedQty;
    }

    /**
     * Registra el conteo de un producto.
     */
    public static function record(
        PhysicalCountItemIdValueObject $id,
        PhysicalCount $physicalCount,
        string $productCode,
        int $expectedQty,
        int $countedQty,
        ?string $notes = null,
    ): self {
        return new self(
            id:            $id,
            physicalCount: $physicalCount,
            productCode:   $productCode,
            expectedQty:   $expectedQty,
            countedQty:    $countedQty,
            notes:         $notes,
        );
    }

    // ─── Getters ──────────────────────────────────────────────────────────────

    public function id(): PhysicalCountItemIdValueObject
    {
        return $this->id;
    }

    public function productCode(): string
    {
        return $this->productCode;
    }

    public function expectedQty(): int
    {
        return $this->expectedQty;
    }

    public function countedQty(): int
    {
        return $this->countedQty;
    }

    public function difference(): int
    {
        return $this->difference;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }
}
