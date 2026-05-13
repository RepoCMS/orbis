<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\Transformation;

final readonly class TransformationResponse
{
    public function __construct(
        public string  $id,
        public string  $serializedStockId,
        public string  $laboratoryId,
        public string  $performedById,
        public string  $originalProductId,
        public ?string $newProductId,
        public ?string $originalSerialNumber,
        public ?string $newSerialNumber,
        public ?string $notes,
        public string  $createdAt,
        public bool    $changedProduct,
        public bool    $changedSerial,
    ) {
    }

    public static function fromEntity(Transformation $t): self
    {
        return new self(
            id:                   (string) $t->id(),
            serializedStockId:    (string) $t->serializedStockId(),
            laboratoryId:         (string) $t->laboratoryId(),
            performedById:        (string) $t->performedById(),
            originalProductId:    (string) $t->originalProductId(),
            newProductId:         $t->newProductId() !== null ? (string) $t->newProductId() : null,
            originalSerialNumber: $t->originalSerialNumber() !== null ? (string) $t->originalSerialNumber() : null,
            newSerialNumber:      $t->newSerialNumber() !== null ? (string) $t->newSerialNumber() : null,
            notes:                $t->notes(),
            createdAt:            $t->createdAt()->format('Y-m-d H:i:s'),
            changedProduct:       $t->changedProduct(),
            changedSerial:        $t->changedSerial(),
        );
    }
}
