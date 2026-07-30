<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UpdateTicket;

use App\Support\Domain\Enums\ServiceType;

final readonly class UpdateTicketCommand
{
    public function __construct(
        public string       $ticketId,
        public ?string      $description = null,
        public ?ServiceType $serviceType = null,
    ) {}
}
