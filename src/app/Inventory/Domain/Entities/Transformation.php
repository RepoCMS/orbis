<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Inventory\Domain\ValueObjects\TransformationIdValueObject;
use DateTimeImmutable;

/**
 * Registro de la transformación de un repuesto en laboratorio.
 *
 * Cuando una pieza es procesada en un laboratorio, puede cambiar:
 *  - Su código de producto (el repuesto se reconvierte en otro tipo).
 *  - Su número de serie (el lab asigna un nuevo serial, o el sistema lo genera).
 *  - Ambos campos.
 *
 * El Application layer es responsable de actualizar el SerializedStock
 * con los nuevos valores y registrar este evento como Transformation.
 */
final class Transformation
{
    public function __construct(
        private TransformationIdValueObject $id,
        private SerializedStockIdValueObject $serializedStockId,
        private LocationIdValueObject $laboratoryId,
        private UserIdValueObject $performedById,
        private ProductIdValueObject $originalProductId,
        private ?ProductIdValueObject $newProductId,
        private ?StockSerialNumberValueObject $originalSerialNumber,
        private ?StockSerialNumberValueObject $newSerialNumber,
        private ?string $notes,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public static function record(
        TransformationIdValueObject $id,
        SerializedStockIdValueObject $serializedStockId,
        LocationIdValueObject $laboratoryId,
        UserIdValueObject $performedById,
        ProductIdValueObject $originalProductId,
        ?ProductIdValueObject $newProductId,
        ?StockSerialNumberValueObject $originalSerialNumber,
        ?StockSerialNumberValueObject $newSerialNumber,
        ?string $notes = null,
    ): self {
        return new self(
            id:                   $id,
            serializedStockId:    $serializedStockId,
            laboratoryId:         $laboratoryId,
            performedById:        $performedById,
            originalProductId:    $originalProductId,
            newProductId:         $newProductId,
            originalSerialNumber: $originalSerialNumber,
            newSerialNumber:      $newSerialNumber,
            notes:                $notes,
            createdAt:            new DateTimeImmutable(),
        );
    }

    public function id(): TransformationIdValueObject
    {
        return $this->id;
    }

    public function serializedStockId(): SerializedStockIdValueObject
    {
        return $this->serializedStockId;
    }

    public function laboratoryId(): LocationIdValueObject
    {
        return $this->laboratoryId;
    }

    public function performedById(): UserIdValueObject
    {
        return $this->performedById;
    }

    public function originalProductId(): ProductIdValueObject
    {
        return $this->originalProductId;
    }

    public function newProductId(): ?ProductIdValueObject
    {
        return $this->newProductId;
    }

    public function originalSerialNumber(): ?StockSerialNumberValueObject
    {
        return $this->originalSerialNumber;
    }

    public function newSerialNumber(): ?StockSerialNumberValueObject
    {
        return $this->newSerialNumber;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function changedProduct(): bool
    {
        return $this->newProductId !== null;
    }

    public function changedSerial(): bool
    {
        return $this->newSerialNumber !== null;
    }
}
