<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\StartPhysicalCount;

final readonly class StartPhysicalCountCommand
{
    /**
     * @param array<array{productCode: string, expectedQty: int, countedQty: int, notes?: string|null}> $items
     */
    public function __construct(
        public string $locationId,
        public string $countedBy,
        public ?string $notes = null,
        public array $items = [],
    ) {
    }
}
