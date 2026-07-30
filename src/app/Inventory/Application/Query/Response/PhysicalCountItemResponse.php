<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\PhysicalCountItem;

final readonly class PhysicalCountItemResponse
{
    public function __construct(
        public string $id,
        public string $productCode,
        public int $expectedQty,
        public int $countedQty,
        public int $difference,
        public ?string $notes,
    ) {
    }

    public static function fromEntity(PhysicalCountItem $item): self
    {
        return new self(
            id:          (string) $item->id(),
            productCode: $item->productCode(),
            expectedQty: $item->expectedQty(),
            countedQty:  $item->countedQty(),
            difference:  $item->difference(),
            notes:       $item->notes(),
        );
    }
}
