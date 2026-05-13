<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\MarkRepairUnrepairable;

final readonly class MarkRepairUnrepairableCommand
{
    public function __construct(
        public string $id,
        public ?string $notes,
    ) {
    }
}
