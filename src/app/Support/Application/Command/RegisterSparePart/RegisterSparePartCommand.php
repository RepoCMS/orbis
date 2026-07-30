<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RegisterSparePart;

final readonly class RegisterSparePartCommand
{
    public function __construct(
        public string  $ticketId,
        public string  $findingId,
        public string  $productCode,
        public string  $userId,
        public ?string $serialNumber = null,
        public int     $quantity = 1,
        public bool    $isIncoming = true,
        public bool    $isVisibleToClient = true,
    ) {}
}
