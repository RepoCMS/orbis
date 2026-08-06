<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CreateTicket;

use App\Support\Domain\Enums\ServiceType;

final readonly class CreateTicketCommand
{
    public function __construct(
        public string      $clientId,
        public string      $terminalAssignmentId,
        public ServiceType $serviceType,
        public string      $description,
        public string      $createdBy,
    ) {
    }
}
