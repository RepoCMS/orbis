<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CreateRmaRecord;

final readonly class CreateRmaRecordCommand
{
    public function __construct(
        public string  $ticketId,
        public string  $productCode,
        public string  $createdBy,
        public ?string $serialNumber = null,
        public int     $quantity = 1,
        public ?string $notes = null,
    ) {
    }
}
