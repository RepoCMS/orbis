<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelTransfer;

final readonly class CancelTransferCommand
{
    public function __construct(
        public string $movementId,
        public string $cancelledBy,
    ) {
    }
}
