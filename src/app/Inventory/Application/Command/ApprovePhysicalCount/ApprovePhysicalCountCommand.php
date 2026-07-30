<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ApprovePhysicalCount;

final readonly class ApprovePhysicalCountCommand
{
    public function __construct(
        public string $countId,
        public string $approvedBy,
    ) {
    }
}
