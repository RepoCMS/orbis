<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\SerialHistoryReason;
use App\Inventory\Domain\ValueObjects\SerialHistoryIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use DateTimeImmutable;

/**
 * Trazabilidad de cambios de número de serie de un repuesto serializado.
 *
 * Cuando una parte serializada es reparada o canjeada, se le asigna un nuevo
 * número de serie. Este registro mantiene la cadena completa de cambios.
 *
 * Ejemplo: serial1 → serial2 → serial3 → ...
 */
final class SerialHistory
{
    public function __construct(
        private SerialHistoryIdValueObject $id,
        private SerializedStockIdValueObject $serializedStockId,
        private StockSerialNumberValueObject $previousSerialNumber,
        private StockSerialNumberValueObject $newSerialNumber,
        private SerialHistoryReason $reason,
        private DateTimeImmutable $changedAt,
    ) {
    }

    public static function record(
        SerialHistoryIdValueObject $id,
        SerializedStockIdValueObject $serializedStockId,
        StockSerialNumberValueObject $previousSerialNumber,
        StockSerialNumberValueObject $newSerialNumber,
        SerialHistoryReason $reason,
    ): self {
        return new self(
            id:                   $id,
            serializedStockId:    $serializedStockId,
            previousSerialNumber: $previousSerialNumber,
            newSerialNumber:      $newSerialNumber,
            reason:               $reason,
            changedAt:            new DateTimeImmutable(),
        );
    }

    public function id(): SerialHistoryIdValueObject
    {
        return $this->id;
    }

    public function serializedStockId(): SerializedStockIdValueObject
    {
        return $this->serializedStockId;
    }

    public function previousSerialNumber(): StockSerialNumberValueObject
    {
        return $this->previousSerialNumber;
    }

    public function newSerialNumber(): StockSerialNumberValueObject
    {
        return $this->newSerialNumber;
    }

    public function reason(): SerialHistoryReason
    {
        return $this->reason;
    }

    public function changedAt(): DateTimeImmutable
    {
        return $this->changedAt;
    }
}
