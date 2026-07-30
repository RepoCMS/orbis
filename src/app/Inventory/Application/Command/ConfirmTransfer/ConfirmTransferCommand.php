<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ConfirmTransfer;

final readonly class ConfirmTransferCommand
{
    public function __construct(
        public string  $movementId,
        public string  $confirmedBy,
    ) {
    }
}
