<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RecordTransformation;

final readonly class RecordTransformationCommand
{
    public function __construct(
        public string  $serializedStockId,
        public string  $laboratoryId,
        public string  $performedById,
        public ?string $newProductId,
        public ?string $newSerialNumber,
        public ?string $notes,
    ) {
    }
}
