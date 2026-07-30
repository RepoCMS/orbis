<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RejectPhysicalCount;

final readonly class RejectPhysicalCountCommand
{
    public function __construct(
        public string $countId,
        public string $reason,
    ) {
    }
}
