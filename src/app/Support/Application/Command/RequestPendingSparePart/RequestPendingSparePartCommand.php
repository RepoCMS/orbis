<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RequestPendingSparePart;

final readonly class RequestPendingSparePartCommand
{
    public function __construct(
        public string  $ticketId,
        public string  $productCode,
        public int     $quantity = 1,
        public ?string $notes = null,
        public string  $userId = '',
    ) {}
}
