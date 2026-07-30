<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RejectTransfer;

final readonly class RejectTransferCommand
{
    public function __construct(
        public string  $movementId,
        public string  $rejectedBy,
        public string  $reason,
        public int     $quantity,
    ) {
    }
}
